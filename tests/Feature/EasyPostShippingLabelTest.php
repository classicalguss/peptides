<?php

namespace Tests\Feature;

use App\Filament\Pages\ShippingSettings;
use App\Mail\OrderShipped;
use App\Models\ShippingSetting;
use App\Models\User;
use App\Shipping\EasyPostClient;
use App\Shipping\EasyPostException;
use App\Shipping\ShipOrder;
use App\Shipping\ShippingLabel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Lunar\Admin\Filament\Resources\OrderResource\Pages\ManageOrder;
use Lunar\Admin\Models\Staff;
use Lunar\Models\Channel;
use Lunar\Models\Country;
use Lunar\Models\Currency;
use Lunar\Models\Language;
use Lunar\Models\Order;
use Lunar\Models\OrderAddress;
use Lunar\Models\OrderLine;
use Lunar\Models\TaxClass;
use RuntimeException;
use Tests\TestCase;

class EasyPostShippingLabelTest extends TestCase
{
    use RefreshDatabase;

    private Country $country;

    protected function setUp(): void
    {
        parent::setUp();

        Language::query()->firstOrCreate(['code' => 'en'], ['name' => 'English', 'default' => true]);
        Currency::factory()->create(['code' => 'USD', 'default' => true, 'enabled' => true]);
        Channel::factory()->create(['default' => true]);
        TaxClass::factory()->create(['default' => true]);
        $this->country = Country::factory()->create(['name' => 'United States', 'iso2' => 'US']);

        config([
            'easypost.api_key' => 'EZTK-test-key',
            'easypost.api_base' => 'https://api.easypost.com/v2',
            'easypost.from' => [
                'name' => 'Fulfilment',
                'company' => 'Powered Up Peptides',
                'street1' => '500 Warehouse Rd',
                'street2' => null,
                'city' => 'Dallas',
                'state' => 'TX',
                'zip' => '75001',
                'country' => 'US',
                'phone' => '5555550100',
            ],
        ]);
    }

    private function order(array $attributes = [], ?string $email = 'ada@example.com'): Order
    {
        $order = Order::factory()->create(array_merge([
            'status' => 'payment-received',
            'currency_code' => 'USD',
            'placed_at' => now(),
            'meta' => [],
        ], $attributes));

        OrderLine::factory()->create([
            'order_id' => $order->id,
            'type' => 'physical',
            'description' => 'BPC-157',
            'option' => '5mg',
            'quantity' => 2,
        ]);

        OrderAddress::factory()->create([
            'order_id' => $order->id,
            'type' => 'shipping',
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'company_name' => null,
            'line_one' => '1 Research Way',
            'line_two' => null,
            'city' => 'Austin',
            'state' => 'TX',
            'postcode' => '73301',
            'country_id' => $this->country->id,
            'contact_email' => $email,
        ]);

        return $order->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function shipmentResponse(): array
    {
        return [
            'id' => 'shp_123',
            'mode' => 'test',
            'rates' => [
                ['id' => 'rate_priority', 'carrier' => 'USPS', 'service' => 'Priority', 'rate' => '9.45', 'delivery_days' => 2],
                ['id' => 'rate_ground', 'carrier' => 'USPS', 'service' => 'GroundAdvantage', 'rate' => '5.10', 'delivery_days' => 4],
            ],
            'messages' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function boughtResponse(): array
    {
        return array_merge($this->shipmentResponse(), [
            'tracking_code' => '9400100000000000000000',
            'postage_label' => ['label_url' => 'https://easypost-files.example/label.pdf'],
            'tracker' => ['public_url' => 'https://track.easypost.com/abc'],
            'selected_rate' => ['id' => 'rate_ground', 'carrier' => 'USPS', 'service' => 'GroundAdvantage', 'rate' => '5.10'],
        ]);
    }

    private function fakeEasyPost(): void
    {
        Http::fake([
            'api.easypost.com/v2/shipments/shp_123/buy' => Http::response($this->boughtResponse()),
            'api.easypost.com/v2/shipments' => Http::response($this->shipmentResponse(), 201),
        ]);
    }

    private function signInAsAdmin(): void
    {
        $this->actingAs(Staff::factory()->create(['admin' => true]), 'staff');
    }

    public function test_creating_a_shipment_sends_the_order_address_and_returns_rates_cheapest_first(): void
    {
        $this->fakeEasyPost();
        $order = $this->order();

        $shipment = app(EasyPostClient::class)->createShipment($order, ['weight_oz' => 8, 'length' => 6, 'width' => 4, 'height' => 2]);

        $this->assertSame('shp_123', $shipment['id']);
        $this->assertSame(['rate_ground', 'rate_priority'], array_column($shipment['rates'], 'id'));
        $this->assertSame(510, $shipment['rates'][0]['rate']);

        Http::assertSent(function (Request $request) use ($order) {
            $shipment = $request['shipment'];

            return $request->url() === 'https://api.easypost.com/v2/shipments'
                && $request->header('Authorization')[0] === 'Basic '.base64_encode('EZTK-test-key:')
                && $shipment['reference'] === $order->reference
                && $shipment['to_address']['name'] === 'Ada Lovelace'
                && $shipment['to_address']['street1'] === '1 Research Way'
                && $shipment['to_address']['zip'] === '73301'
                && $shipment['to_address']['country'] === 'US'
                && $shipment['from_address']['street1'] === '500 Warehouse Rd'
                && $shipment['parcel']['weight'] === 8.0;
        });
    }

    public function test_an_easypost_error_is_surfaced_with_its_field_details(): void
    {
        Http::fake(['api.easypost.com/*' => Http::response([
            'error' => [
                'code' => 'ADDRESS.VERIFY.FAILURE',
                'message' => 'Unable to verify address.',
                'errors' => [['field' => 'zip', 'message' => 'is invalid']],
            ],
        ], 422)]);

        $this->expectException(EasyPostException::class);
        $this->expectExceptionMessage('Unable to verify address. (zip is invalid)');

        app(EasyPostClient::class)->createShipment($this->order(), ['weight_oz' => 8, 'length' => 6, 'width' => 4, 'height' => 2]);
    }

    public function test_a_shipment_with_no_rates_reports_the_carrier_messages(): void
    {
        Http::fake(['api.easypost.com/*' => Http::response([
            'id' => 'shp_123',
            'rates' => [],
            'messages' => [['carrier' => 'USPS', 'message' => 'Parcel is too heavy.']],
        ], 201)]);

        $this->expectException(EasyPostException::class);
        $this->expectExceptionMessage('Parcel is too heavy.');

        app(EasyPostClient::class)->createShipment($this->order(), ['weight_oz' => 8, 'length' => 6, 'width' => 4, 'height' => 2]);
    }

    public function test_an_incomplete_ship_from_address_never_reaches_easypost(): void
    {
        Http::fake();
        config(['easypost.from.zip' => null]);

        try {
            app(EasyPostClient::class)->createShipment($this->order(), ['weight_oz' => 8, 'length' => 6, 'width' => 4, 'height' => 2]);
            $this->fail('Expected an EasyPostException.');
        } catch (EasyPostException $e) {
            $this->assertStringContainsString('zip is missing', $e->getMessage());
        }

        Http::assertNothingSent();
    }

    public function test_buying_a_label_records_tracking_dispatches_the_order_and_emails_the_customer(): void
    {
        Mail::fake();
        $this->fakeEasyPost();
        $order = $this->order(['meta' => ['confirmation_emailed_at' => '2026-10-01T00:00:00+00:00']]);

        $label = app(ShipOrder::class)->handle($order, 'shp_123', 'rate_ground');

        $order->refresh();

        $this->assertSame('9400100000000000000000', $label->trackingCode);
        $this->assertSame('dispatched', $order->status);
        $this->assertSame('2026-10-01T00:00:00+00:00', $order->meta['confirmation_emailed_at']);
        $this->assertSame('9400100000000000000000', $order->meta['easypost']['tracking_code']);
        $this->assertSame('https://easypost-files.example/label.pdf', $order->meta['easypost']['label_url']);
        $this->assertSame(510, $order->meta['easypost']['rate']);
        $this->assertTrue($order->meta['easypost']['test']);

        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/shipments/shp_123/buy')
            && $request['rate']['id'] === 'rate_ground');

        Mail::assertSent(OrderShipped::class, fn (OrderShipped $mail) => $mail->hasTo('ada@example.com') && $mail->order->is($order));
    }

    public function test_an_order_that_already_has_a_label_is_not_charged_again(): void
    {
        Mail::fake();
        $this->fakeEasyPost();
        $order = $this->order();

        app(ShipOrder::class)->handle($order, 'shp_123', 'rate_ground');

        try {
            app(ShipOrder::class)->handle($order->fresh(), 'shp_123', 'rate_ground');
            $this->fail('Expected an EasyPostException.');
        } catch (EasyPostException $e) {
            $this->assertStringContainsString('already been bought', $e->getMessage());
        }

        Http::assertSentCount(1);
        Mail::assertSent(OrderShipped::class, 1);
    }

    public function test_a_failed_purchase_leaves_the_order_untouched(): void
    {
        Mail::fake();
        Http::fake(['api.easypost.com/*' => Http::response(['error' => ['message' => 'Insufficient funds.']], 402)]);
        $order = $this->order();

        try {
            app(ShipOrder::class)->handle($order, 'shp_123', 'rate_ground');
            $this->fail('Expected an EasyPostException.');
        } catch (EasyPostException $e) {
            $this->assertSame('Insufficient funds.', $e->getMessage());
        }

        $order->refresh();

        $this->assertSame('payment-received', $order->status);
        $this->assertNull(ShippingLabel::forOrder($order));
        Mail::assertNothingSent();
    }

    public function test_a_mail_failure_does_not_undo_a_bought_label(): void
    {
        $this->fakeEasyPost();
        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('mailgun is down'));
        $order = $this->order();

        app(ShipOrder::class)->handle($order, 'shp_123', 'rate_ground');

        $this->assertNotNull(ShippingLabel::forOrder($order->fresh()));
    }

    public function test_the_shipping_email_shows_the_tracking_details(): void
    {
        $order = $this->order();
        $label = ShippingLabel::fromResponse($this->boughtResponse());

        $mail = new OrderShipped($order, $label);
        $html = $mail->render();

        $this->assertStringContainsString("Order {$order->reference} shipped", $mail->envelope()->subject);
        $this->assertStringContainsString('Good news, Ada', $html);
        $this->assertStringContainsString('9400100000000000000000', $html);
        $this->assertStringContainsString('USPS', $html);
        $this->assertStringContainsString('https://track.easypost.com/abc', $html);
        $this->assertStringContainsString('BPC-157 — 5mg', $html);
    }

    public function test_an_admin_can_buy_a_label_from_the_order_screen(): void
    {
        Mail::fake();
        $this->fakeEasyPost();
        $this->signInAsAdmin();
        $order = $this->order();

        Livewire::test(ManageOrder::class, ['record' => $order->getRouteKey()])
            ->assertActionVisible('buy_shipping_label')
            ->assertActionHidden('print_shipping_label')
            ->mountAction('buy_shipping_label')
            ->assertActionDataSet(['weight_oz' => 8.0])
            ->goToNextWizardStep(formName: 'mountedActionForm')
            ->assertActionDataSet([
                'shipment_id' => 'shp_123',
                'rate_id' => 'rate_ground',
                'rates' => [
                    'rate_ground' => 'USPS GroundAdvantage — $5.10 (4 days)',
                    'rate_priority' => 'USPS Priority — $9.45 (2 days)',
                ],
            ])
            ->callMountedAction()
            ->assertHasNoActionErrors()
            ->assertActionHidden('buy_shipping_label')
            ->assertActionVisible('print_shipping_label')
            ->assertActionHasUrl('print_shipping_label', 'https://easypost-files.example/label.pdf');

        $this->assertSame('dispatched', $order->fresh()->status);
        Mail::assertSent(OrderShipped::class, 1);
    }

    public function test_a_rate_failure_keeps_the_admin_on_the_package_step(): void
    {
        Http::fake(['api.easypost.com/*' => Http::response(['error' => ['message' => 'Unable to verify address.']], 422)]);
        $this->signInAsAdmin();
        $order = $this->order();

        Livewire::test(ManageOrder::class, ['record' => $order->getRouteKey()])
            ->mountAction('buy_shipping_label')
            ->goToNextWizardStep(formName: 'mountedActionForm')
            ->assertWizardCurrentStep(1, formName: 'mountedActionForm')
            ->assertNotified('Could not get shipping rates');

        $this->assertNull(ShippingLabel::forOrder($order->fresh()));
    }

    public function test_the_label_actions_are_hidden_until_an_api_key_is_configured(): void
    {
        config(['easypost.api_key' => null]);
        $this->signInAsAdmin();
        $order = $this->order();

        Livewire::test(ManageOrder::class, ['record' => $order->getRouteKey()])
            ->assertActionHidden('buy_shipping_label')
            ->assertActionHidden('print_shipping_label');
    }

    public function test_an_admin_can_set_the_ship_from_address_and_it_is_used_on_labels(): void
    {
        $this->fakeEasyPost();
        $this->signInAsAdmin();
        config(['easypost.from' => ['country' => 'US']]);

        $this->get('/lunar/shipping-settings')->assertOk()->assertSee('TEST mode');

        Livewire::test(ShippingSettings::class)
            ->fillForm([
                'from_name' => 'Sam Sender',
                'from_street1' => '9 Depot St',
                'from_city' => 'Miami',
                'from_state' => 'fl',
                'from_zip' => '33101',
                'from_phone' => '5555550199',
                'parcel_weight_oz' => 12,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(1, ShippingSetting::query()->count());
        $this->assertSame(12.0, ShippingSetting::current()->parcel()['weight_oz']);

        app(EasyPostClient::class)->createShipment($this->order(), ShippingSetting::current()->parcel());

        Http::assertSent(fn (Request $request) => $request['shipment']['from_address']['street1'] === '9 Depot St'
            && $request['shipment']['from_address']['state'] === 'FL'
            && $request['shipment']['from_address']['country'] === 'US'
            && $request['shipment']['parcel']['weight'] === 12.0);
    }

    public function test_the_ship_from_address_is_required_in_the_admin(): void
    {
        $this->signInAsAdmin();
        config(['easypost.from' => ['country' => 'US']]);

        Livewire::test(ShippingSettings::class)
            ->call('save')
            ->assertHasFormErrors(['from_street1', 'from_city', 'from_state', 'from_zip', 'from_phone']);

        $this->assertSame(0, ShippingSetting::query()->count());
    }

    public function test_a_customer_sees_tracking_on_their_order_page(): void
    {
        $user = User::factory()->create();
        $order = $this->order(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('account.order', $order->reference))
            ->assertOk()
            ->assertDontSee('Track Package');

        $order->update(['meta' => ['easypost' => ShippingLabel::fromResponse($this->boughtResponse())->toMeta()]]);

        $this->actingAs($user)
            ->get(route('account.order', $order->reference))
            ->assertOk()
            ->assertSee('9400100000000000000000')
            ->assertSee('https://track.easypost.com/abc');
    }
}

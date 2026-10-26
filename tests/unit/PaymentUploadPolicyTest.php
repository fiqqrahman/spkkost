<?php

use App\Libraries\PaymentUploadPolicy;
use CodeIgniter\Test\CIUnitTestCase;

final class PaymentUploadPolicyTest extends CIUnitTestCase
{
    public function testPaymentRulesRequirePositiveAmountAndImageUpload(): void
    {
        $rules = PaymentUploadPolicy::rules();

        $this->assertSame('required|numeric', $rules['booking_id']);
        $this->assertSame('required|numeric|greater_than[0]', $rules['amount']);
        $this->assertStringContainsString('uploaded[proof_image]', $rules['proof_image']['rules']);
        $this->assertStringContainsString('is_image[proof_image]', $rules['proof_image']['rules']);
        $this->assertStringContainsString('mime_in[proof_image,image/jpg,image/jpeg,image/png,image/webp]', $rules['proof_image']['rules']);
        $this->assertStringContainsString('max_size[proof_image,2048]', $rules['proof_image']['rules']);

        $validator = service('validation');
        $validator->setRules(['amount' => $rules['amount']]);
        $this->assertTrue($validator->run(['amount' => '125000']));
        $this->assertFalse($validator->run(['amount' => '0']));
    }
}

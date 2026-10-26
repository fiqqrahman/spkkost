<?php

namespace App\Libraries;

final class PaymentUploadPolicy
{
    /** @return array<string, array{rules: string, errors: array<string, string>}|string> */
    public static function rules(): array
    {
        return [
            'booking_id' => 'required|numeric',
            'amount'     => 'required|numeric|greater_than[0]',
            'proof_image' => [
                'rules' => 'uploaded[proof_image]'
                    . '|is_image[proof_image]'
                    . '|mime_in[proof_image,image/jpg,image/jpeg,image/png,image/webp]'
                    . '|max_size[proof_image,2048]',
                'errors' => [
                    'uploaded' => 'Harap sertakan foto resi/bukti transfer.',
                    'is_image' => 'File bukti bayar harus berupa gambar valid.',
                    'mime_in'  => 'Ekstensi gambar yang diizinkan: JPG, JPEG, PNG, WEBP.',
                    'max_size' => 'Ukuran file maksimal 2MB.',
                ],
            ],
        ];
    }
}

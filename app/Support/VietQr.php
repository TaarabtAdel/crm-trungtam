<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VietQr
{
    /**
     * BIN → tên ngân hàng (theo danh sách VietQR phổ biến).
     *
     * @return array<string, string>
     */
    public static function banks(): array
    {
        return [
            '970436' => 'Vietcombank (VCB)',
            '970415' => 'VietinBank (CTG)',
            '970418' => 'BIDV',
            '970405' => 'Agribank',
            '970422' => 'MB Bank',
            '970407' => 'Techcombank (TCB)',
            '970432' => 'VPBank',
            '970423' => 'TPBank',
            '970403' => 'Sacombank (STB)',
            '970441' => 'VIB',
            '970448' => 'OCB',
            '970414' => 'MSB',
            '970437' => 'HDBank',
            '970431' => 'Eximbank',
            '970426' => 'Maritime Bank',
            '970440' => 'SeABank',
            '970443' => 'SHB',
            '970449' => 'LienVietPostBank (LPBank)',
            '970458' => 'Bac A Bank',
            '970400' => 'Saigonbank',
            '970409' => 'BacABank',
            '970424' => 'Shinhan Bank',
            '970428' => 'Nam A Bank',
            '970430' => 'PVcomBank',
            '970438' => 'BaoViet Bank',
            '970446' => 'Co-opBank',
            '970452' => 'Kien Long Bank',
            '970454' => 'Viet Capital Bank (BVBank)',
            '970457' => 'Woori Bank',
            '970462' => 'Kookmin Bank',
        ];
    }

    public static function bankName(?string $bin): ?string
    {
        if (! $bin) {
            return null;
        }

        return self::banks()[$bin] ?? null;
    }

    public static function imageUrl(
        string $bin,
        string $accountNumber,
        ?string $accountName = null,
        float|int|null $amount = null,
        ?string $addInfo = null,
        string $template = 'compact2'
    ): string {
        $accountNumber = preg_replace('/\s+/', '', $accountNumber) ?: '';
        $url = sprintf(
            'https://img.vietqr.io/image/%s-%s-%s.png',
            rawurlencode($bin),
            rawurlencode($accountNumber),
            rawurlencode($template)
        );

        $query = array_filter([
            'amount' => $amount !== null && (float) $amount > 0 ? (int) round((float) $amount) : null,
            'addInfo' => $addInfo ? mb_substr(preg_replace('/\s+/', ' ', trim($addInfo)) ?? '', 0, 100) : null,
            'accountName' => $accountName ? mb_substr(trim($accountName), 0, 70) : null,
        ], fn ($v) => $v !== null && $v !== '');

        return $query === [] ? $url : $url.'?'.http_build_query($query);
    }

    public static function dataUriFromUrl(string $url): ?string
    {
        try {
            $response = Http::timeout(8)->withHeaders([
                'Accept' => 'image/png,image/*',
            ])->get($url);

            if (! $response->successful()) {
                return null;
            }

            $body = $response->body();
            if ($body === '') {
                return null;
            }

            $mime = $response->header('Content-Type') ?: 'image/png';
            if (! str_starts_with($mime, 'image/')) {
                $mime = 'image/png';
            }

            return 'data:'.$mime.';base64,'.base64_encode($body);
        } catch (\Throwable $e) {
            Log::warning('VietQR fetch failed: '.$e->getMessage());

            return null;
        }
    }
}

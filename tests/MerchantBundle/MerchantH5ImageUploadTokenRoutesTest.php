<?php

declare(strict_types=1);

namespace Tests\MerchantBundle;

/** @see .tasks/plans/merchant-h5-image-upload-token.md TC-04 */
class MerchantH5ImageUploadTokenRoutesTest extends \TestCase
{
    public function testMerchantImageUploadTokenRouteInFrontMerchantAuthGroup(): void
    {
        // #given merchant routes file
        $merchantRoutes = (string) file_get_contents(dirname(__DIR__, 2).'/routes/frontapi/merchant.php');
        $uploadGroup = $this->extractGroupBlockContaining($merchantRoutes, '/wxapp/merchant/espier/image_upload_token');

        // #then token route lives in frontmerchantauth + Espier UploadFile namespace (A1, A5)
        $this->assertStringContainsString(
            'frontmerchantauth:h5app',
            $uploadGroup,
            'Merchant image_upload_token must use frontmerchantauth:h5app'
        );
        $this->assertStringContainsString(
            'EspierBundle\\Http\\FrontApi\\V1\\Action',
            $uploadGroup,
            'Merchant image_upload_token group must use EspierBundle UploadFile namespace'
        );
        $this->assertStringContainsString(
            'UploadFile@getPicUploadToken',
            $uploadGroup,
            'Merchant image_upload_token must use UploadFile@getPicUploadToken'
        );
        $this->assertStringContainsString(
            'merchant.h5app.image.uptoken.get',
            $uploadGroup,
            'Merchant image_upload_token route name must be merchant.h5app.image.uptoken.get'
        );
        $this->assertStringNotContainsString(
            'MerchantBundle\\Http\\FrontApi\\V1\\Action\\EspierBundle',
            $merchantRoutes,
            'Must not concatenate MerchantBundle namespace with Espier FQCN'
        );

        // #given member auth routes unchanged
        $authRoutes = (string) file_get_contents(dirname(__DIR__, 2).'/routes/frontapi/auth.php');

        // #then original member path and dingoguard middleware remain (A6)
        $this->assertStringContainsString('/wxapp/espier/image_upload_token', $authRoutes);
        $this->assertStringContainsString('dingoguard:h5app', $authRoutes);
    }

    private function extractGroupBlockContaining(string $content, string $needle): string
    {
        $needlePos = strpos($content, $needle);
        $this->assertNotFalse($needlePos, "Route content containing {$needle} must exist");

        $groupStart = strrpos(substr($content, 0, $needlePos), '$api->group');
        $this->assertNotFalse($groupStart, 'Surrounding group declaration must exist');

        $functionPos = strpos($content, 'function ($api) {', $groupStart);
        $this->assertNotFalse($functionPos, 'Group callback must exist');

        $openBrace = strpos($content, '{', $functionPos);
        $this->assertNotFalse($openBrace, 'Group body must exist');

        $depth = 0;
        $length = strlen($content);
        for ($i = $openBrace; $i < $length; $i++) {
            $char = $content[$i];
            if ($char === '{') {
                $depth++;
            } elseif ($char === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($content, $groupStart, $i - $groupStart + 1);
                }
            }
        }

        $this->fail('Group block must be closed');
    }
}

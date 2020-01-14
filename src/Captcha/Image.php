<?php

namespace Dcat\Utils\Captcha;

use Intervention\Image\Image as InterventionImage;

class Image
{
    /**
     * @var string
     */
    protected $key;

    /**
     * @var InterventionImage
     */
    protected $image;

    /**
     * @var int
     */
    protected $quality = 90;

    public function __construct(string $key, InterventionImage $image, int $quality = 90)
    {
        $this->key = $key;
        $this->image = $image;
        $this->quality = $quality;
    }

    /**
     * @return mixed
     */
    public function response()
    {
        return $this->image->response('png', $this->quality);
    }

    /**
     * @return string
     */
    public function encode()
    {
        return $this->image->encode('data-url')->encoded;
    }

    /**
     * 获取验证码
     *
     * @return string
     */
    public function getCode()
    {
        return $this->key;
    }

    /**
     * @return InterventionImage
     */
    public function getImage()
    {
        return $this->image;
    }
}

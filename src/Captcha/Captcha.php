<?php

namespace Dcat\Utils\Captcha;

/**
 * Laravel 5 & 6 Captcha package.
 *
 * @copyright Copyright (c) 2015 MeWebStudio
 * @version 2.x
 * @author Muharrem ERİN
 * @contact me@mewebstudio.com
 * @web http://www.mewebstudio.com
 * @date 2015-04-03
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

use Exception;
use Intervention\Image\Gd\Font;
use Intervention\Image\Image as InterventionImage;
use Intervention\Image\ImageManager;
use Symfony\Component\Finder\Finder;

/**
 * @require symfony/finder intervention/image
 */
class Captcha
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var ImageManager
     */
    protected $imageManager;

    /**
     * @var ImageManager->canvas
     */
    protected $canvas;

    /**
     * @var InterventionImage
     */
    protected $image;

    /**
     * @var array
     */
    protected $backgrounds = [];

    /**
     * @var array
     */
    protected $fonts = [];

    /**
     * @var array
     */
    protected $fontColors = [];

    /**
     * @var int
     */
    protected $length = 5;

    /**
     * @var int
     */
    protected $width = 120;

    /**
     * @var int
     */
    protected $height = 36;

    /**
     * @var int
     */
    protected $angle = 15;

    /**
     * @var int
     */
    protected $lines = 3;

    /**
     * @var string
     */
    protected $characters;

    /**
     * @var array
     */
    protected $text;

    /**
     * @var int
     */
    protected $contrast = 0;

    /**
     * @var int
     */
    protected $quality = 90;

    /**
     * @var int
     */
    protected $sharpen = 0;
    /**
     * @var int
     */
    protected $blur = 0;

    /**
     * @var bool
     */
    protected $bgImage = true;

    /**
     * @var string
     */
    protected $bgColor = '#ffffff';

    /**
     * @var bool
     */
    protected $invert = false;

    /**
     * @var bool
     */
    protected $math = false;

    /**
     * @var int
     */
    protected $textLeftPadding = 4;

    /**
     * @var string
     */
    protected $fontsDirectory;

    /**
     * Constructor.
     *
     * @param array        $config
     * @param ImageManager $imageManager
     *
     * @throws Exception
     * @internal param Validator $validator
     */
    public function __construct(
        array $config,
        ImageManager $imageManager
    ) {
        $this->config = $config;
        $this->imageManager = $imageManager;
        $this->characters = $config['characters'] ?? ['1', '2', '3', '4', '6', '7', '8', '9'];
        $this->fontsDirectory = $config['fonts_directory'] ?? __DIR__.'/assets/fonts';
    }

    /**
     * 生成图片.
     *
     * @param string $config
     * @return Image
     * @throws Exception
     */
    public static function create(string $config = 'default')
    {
        return static::make()->build($config);
    }

    /**
     * @param array             $config
     * @param ImageManager|null $imageManager
     *
     * @return static
     * @throws Exception
     */
    public static function make(array $config = null, ImageManager $imageManager = null)
    {
        if (! $config && function_exists('config')) {
            $config = config('captcha');
        }

        return new static(
            $config,
            $imageManager ?: new ImageManager()
        );
    }

    /**
     * @param string $config
     * @return void
     */
    protected function configure($config)
    {
        if (isset($this->config[$config])) {
            foreach ($this->config[$config] as $key => $val) {
                $this->{$key} = $val;
            }
        }
    }

    /**
     * Create captcha image.
     *
     * @param string $config
     * @param bool $api
     * @return Image
     * @throws Exception
     */
    public function build(string $config = 'default')
    {
        $this->backgrounds = $this->files(__DIR__.'/assets/backgrounds');
        $this->fonts = $this->files($this->fontsDirectory);

        $this->fonts = array_map(function ($file) {
            return $file->getPathName();
        }, $this->fonts);

        $this->fonts = array_values($this->fonts); //reset fonts array index

        $this->configure($config);

        $generator = $this->generate();
        $this->text = $generator['value'];

        $this->canvas = $this->imageManager->canvas(
            $this->width,
            $this->height,
            $this->bgColor
        );

        if ($this->bgImage) {
            $this->image = $this->imageManager->make($this->background())->resize(
                $this->width,
                $this->height
            );
            $this->canvas->insert($this->image);
        } else {
            $this->image = $this->canvas;
        }

        if ($this->contrast != 0) {
            $this->image->contrast($this->contrast);
        }

        $this->writeText();
        $this->drawLines();

        if ($this->sharpen) {
            $this->image->sharpen($this->sharpen);
        }

        if ($this->invert) {
            $this->image->invert();
        }

        if ($this->blur) {
            $this->image->blur($this->blur);
        }

        return new Image($generator['key'], $this->image, $this->quality);
    }

    /**
     * @param  string $directory
     * @param  bool   $hidden
     *
     * @return \Symfony\Component\Finder\SplFileInfo[]
     */
    protected function files($directory, $hidden = false)
    {
        return iterator_to_array(
            Finder::create()->files()->ignoreDotFiles(! $hidden)->in($directory)->depth(0)->sortByName(),
            false
        );
    }

    /**
     * Image backgrounds.
     *
     * @return string
     */
    protected function background(): string
    {
        return $this->backgrounds[rand(0, count($this->backgrounds) - 1)];
    }

    /**
     * Generate captcha text.
     *
     * @return array
     * @throws Exception
     */
    protected function generate(): array
    {
        $characters = is_string($this->characters) ? str_split($this->characters) : $this->characters;
        $bag = [];

        if ($this->math) {
            $x = random_int(10, 30);
            $y = random_int(1, 9);
            $bag = "$x + $y = ";
            $key = $x + $y;
        } else {
            for ($i = 0; $i < $this->length; $i++) {
                $bag[] = $characters[rand(0, count($characters) - 1)];
            }
            $key = implode('', $bag);
        }

        return [
            'value' => $bag,
            'key'   => (string) $key,
        ];
    }

    /**
     * Writing captcha text.
     *
     * @return void
     */
    protected function writeText(): void
    {
        $marginTop = $this->image->height() / $this->length;

        $text = $this->text;
        if (is_string($text)) {
            $text = str_split($text);
        }

        foreach ($text as $key => $char) {
            $marginLeft = $this->textLeftPadding + ($key * ($this->image->width() - $this->textLeftPadding) / $this->length);
            $this->image->text($char, $marginLeft, $marginTop, function ($font) {
                /* @var Font $font */
                $font->file($this->font());
                $font->size($this->fontSize());
                $font->color($this->fontColor());
                $font->align('left');
                $font->valign('top');
                $font->angle($this->angle());
            });
        }
    }

    /**
     * Image fonts.
     *
     * @return string
     */
    protected function font(): string
    {
        return $this->fonts[rand(0, count($this->fonts) - 1)];
    }

    /**
     * Random font size.
     *
     * @return int
     */
    protected function fontSize(): int
    {
        return rand($this->image->height() - 10, $this->image->height());
    }

    /**
     * Random font color.
     *
     * @return string
     */
    protected function fontColor(): string
    {
        if (! empty($this->fontColors)) {
            $color = $this->fontColors[rand(0, count($this->fontColors) - 1)];
        } else {
            $color = '#'.str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
        }

        return $color;
    }

    /**
     * Angle.
     *
     * @return int
     */
    protected function angle(): int
    {
        return rand((-1 * $this->angle), $this->angle);
    }

    /**
     * Random image lines.
     *
     * @return Image|ImageManager
     */
    protected function drawLines()
    {
        for ($i = 0; $i <= $this->lines; $i++) {
            $this->image->line(
                rand(0, $this->image->width()) + $i * rand(0, $this->image->height()),
                rand(0, $this->image->height()),
                rand(0, $this->image->width()),
                rand(0, $this->image->height()),
                function ($draw) {
                    /* @var Font $draw */
                    $draw->color($this->fontColor());
                }
            );
        }

        return $this->image;
    }
}

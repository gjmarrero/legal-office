<?php

namespace AntonAm\PDFVersionConverter\Converter;

use PHPUnit\Framework\TestCase;
use AntonAm\PDFVersionConverter\Guesser\RegexGuesser;

/**
 * This file is part of the PDF Version Converter.
 * (c) Thiago Rodrigues <xthiago@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Thiago Rodrigues <xthiago@gmail.com>
 */
class GhostscriptConverterCommandTest extends TestCase
{
    protected $tmp;

    protected $files = [
        'text',
        'image.png',
        'v1.0.pdf',
        'v1.1.pdf',
        'v1.2.pdf',
        'v1.3.pdf',
        'v1.4.pdf',
        'v1.5.pdf',
        'v1.6.pdf',
        'v1.7.pdf',
        'v2.0.pdf',
    ];

    protected function setUp(): void
    {
        $this->tmp = realpath(__DIR__ . '/../files/stage/') . '/';

        if (!file_exists($this->tmp)) {
            mkdir($this->tmp);
        }

        $this->copyFilesToStageArea();
    }

    protected function copyFilesToStageArea()
    {
        foreach ($this->files as $file) {
            if (!copy(__DIR__ . '/../files/repo/' . $file, $this->tmp . $file)) {
                throw new \RuntimeException("Can't create test file.");
            }
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            unlink($this->tmp . $file);
        }
        array_map('unlink', glob($this->tmp . 'pdf_version_changer_test_*'));
    }

    /**
     * @param string $file
     * @param string $newVersion
     * @dataProvider filesProvider
     */
    public function testMustConvertPDFVersionWithSuccess($file, $newVersion)
    {
        $tmpFile = $this->tmp . uniqid('pdf_version_changer_test_', false) . '.pdf';

        $command = new GhostscriptConverterCommand();

        $command->run(
            $file,
            $tmpFile,
            $newVersion
        );

        $guesser = new RegexGuesser();
        $version = $guesser->guess($tmpFile);

        $this->assertEquals($version, $newVersion);
    }

    /**
     * @param string $invalidFile
     * @param string $newVersion
     * @dataProvider invalidFilesProvider
     */
    public function testMustThrowException($invalidFile, $newVersion)
    {
        $this->expectException('RuntimeException');
        $tmpFile = $this->tmp . uniqid('pdf_version_changer_test_', false) . '.pdf';

        $command = new GhostscriptConverterCommand();
        $command->run(
            $invalidFile,
            $tmpFile,
            $newVersion
        );

        $guesser = new RegexGuesser();
        $version = $guesser->guess($tmpFile);

        $this->assertEquals($version, $newVersion);
    }

    /**
     * @return array
     */
    public static function filesProvider()
    {
        return [
            // file, new version
            [__DIR__ . '/../files/stage/v1.1.pdf', '1.4'],
            [__DIR__ . '/../files/stage/v1.2.pdf', '1.4'],
            [__DIR__ . '/../files/stage/v1.3.pdf', '1.4'],
            [__DIR__ . '/../files/stage/v1.4.pdf', '1.4'],
            [__DIR__ . '/../files/stage/v1.5.pdf', '1.4'],
            [__DIR__ . '/../files/stage/v1.6.pdf', '1.4'],
            [__DIR__ . '/../files/stage/v1.7.pdf', '1.4'],
            [__DIR__ . '/../files/stage/v2.0.pdf', '1.4'],
        ];
    }

    /**
     * @return array
     */
    public static function invalidFilesProvider()
    {
        return [
            // file, new version
            [__DIR__ . '/../files/stage/text', '1.4'],
            [__DIR__ . '/../files/stage/image.png', '1.5'],
            [__DIR__ . '/../files/stage/dont-exists.pdf', '1.5'],
        ];
    }
}
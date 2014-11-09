<?php
namespace Phpeel\UzukiXml\Tests;

use Phpeel\UzukiXml\UzukiXml;

class UzukiXmlTest extends \PHPUnit_Framework_TestCase
{
    public function providerRender()
    {
        return [
            [ [] ],
            [ [ 'name' => 'hogehoge' ] ],
            [ [ 'members' => [ [ 'name' => 'hogehoge' ], [ 'name' => 'foobar' ] ] ] ]
        ];
    }

    /**
     * @dataProvider providerRender
     */
    public function testRender($variables)
    {
        $options = [
            'SuperParentName'     => 'xml_body',
            'DefaultListItemName' => 'list_item',
        ];
        $uzuki = new UzukiXml($options);

        $expected = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

        if (count($variables) > 0) {
            $expected .= '<xml_body>';

            foreach ($variables as $key => $item) {
                if (is_array($item)) {
                    $tag_name = $options['DefaultListItemName'];
                    $expected .= "<{$key}>";

                    foreach ($item as $sub_item) {
                        $expected .= "<{$tag_name}>";

                        foreach ($sub_item as $sub_key => $sub_value) {
                            $expected .= "<{$sub_key}>{$sub_value}</{$sub_key}>";
                        }

                        $expected .= "</{$tag_name}>";
                    }

                    $expected .= "</{$key}>";
                } else {
                    $expected .= "<{$key}>{$item}</{$key}>";
                }
            }

            $expected .= '</xml_body>' . "\n";
        } else {
            $expected .= '<xml_body/>' . "\n";
        }

        $this->assertSame($expected, $uzuki->render($variables));
    }
}

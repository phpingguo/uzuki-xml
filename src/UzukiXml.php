<?php
namespace Phpeel\UzukiXml;

use Phpeel\ApricotLib\Common\Arrays;
use Phpeel\ApricotLib\Common\String;
use Phpeel\ApricotLib\Enums\Charset;
use Phpeel\CitronDI\AuraDIWrapper;

/**
 * XMLデータを生成してレンダリング出力するクラスです。
 *
 * @final [継承禁止クラス]
 * @author hiroki sugawara
 */
final class UzukiXml
{
    // ---------------------------------------------------------------------------------------------
    // class fields
    // ---------------------------------------------------------------------------------------------
    const OPTION_VERSION = 'Version';
    const OPTION_CHARSET = 'Charset';
    const OPTION_SUPER_PARENT_NAME = 'SuperParentName';
    const OPTION_DEFAULT_LIST_ITEM_NAME = 'DefaultListItemName';

    // ---------------------------------------------------------------------------------------------
    // private fields
    // ---------------------------------------------------------------------------------------------
    private $options      = [];
    private $dom_document = null;

    // ---------------------------------------------------------------------------------------------
    // constructor / destructor
    // ---------------------------------------------------------------------------------------------
    /**
     * UzukiXmlクラスの新しいインスタンスを初期化します。
     *
     * @param Array $options [初期値=array()]
     */
    public function __construct(array $options = [])
    {
        $this->setOptions($options);
        $this->setDomDocument($this->getNewDomDocument());
    }

    // ---------------------------------------------------------------------------------------------
    // public member methods
    // ---------------------------------------------------------------------------------------------
    /**
     * パラメータとして渡された変数の一覧を使用してXMLデータをレンダリングします。
     *
     * @param Array $variables [初期値=array()] XMLデータとしてレンダリングする変数の一覧
     *
     * @return String レンダリングされたXMLデータの文字列
     */
    public function render(array $variables = [])
    {
        $this->getDomDocument()->appendChild($this->getBodyElements($variables));

        return $this->getDomDocument()->saveXML();
    }

    // ---------------------------------------------------------------------------------------------
    // private member methods
    // ---------------------------------------------------------------------------------------------
    /**
     * DomDocumentクラスの新しいインスタンスを取得します。
     *
     * @return \DomDocument 新しく生成したDomDocumentクラスのインスタンス
     */
    private function getNewDomDocument()
    {
        return AuraDIWrapper::getInstantContainer()->newInstance(
            'DomDocument',
            [
                $this->getOption(static::OPTION_VERSION),
                $this->getOption(static::OPTION_CHARSET)
            ]
        );
    }

    /**
     * レンダリングに使用するオプションデータを設定します。
     *
     * @param Array $options 設定するオプションデータの連想配列
     */
    private function setOptions(array $options)
    {
        $this->options = $options;

        $this->addOption(static::OPTION_VERSION, '1.0');
        $this->addOption(static::OPTION_CHARSET, Charset::UTF8);
        $this->addOption(static::OPTION_SUPER_PARENT_NAME, 'xml_body');
        $this->addOption(static::OPTION_DEFAULT_LIST_ITEM_NAME, 'list_item');
    }

    /**
     * レンダリングに使用するオプションデータに設定値を追加します。
     *
     * @param String|Integer $key 追加するオプション設定の名前
     * @param mixed $value        追加するオプション設定の値
     */
    private function addOption($key, $value)
    {
        Arrays::addWhen(Arrays::isExist($this->options, $key) === false, $this->options, $value, $key);
    }

    /**
     * レンダリングオプションデータから指定した設定名の値を取得します。
     *
     * @param String $option_name [初期値=null] 値を取得する設定の名前
     * @param Array $default [初期値=array()]   設定名が存在しない場合に使われる値
     *
     * @return mixed レンダリングオプションデータから指定した設定名の値
     */
    private function getOption($option_name = null, $default = [])
    {
        return Arrays::findValue($this->options, $option_name, $default);
    }

    /**
     * DomDocumentクラスのインスタンスを設定します。
     *
     * @param \DOMDocument $instance 新しく設定するDomDocumentクラスのインスタンス
     */
    private function setDomDocument(\DOMDocument $instance)
    {
        $this->dom_document = $instance;
    }

    /**
     * DomDocumentクラスのインスタンスを取得します。
     *
     * @return \DomDocument このクラスが保持するDomDocumentクラスのインスタンス
     */
    private function getDomDocument()
    {
        return $this->dom_document;
    }

    /**
     * XMLの本体タグのインスタンスを取得します。
     *
     * @param Array $variables モジュールインスタンスが保持する変数の一覧
     *
     * @return \DOMElement XMLの本体タグのインスタンス
     */
    private function getBodyElements(array $variables)
    {
        $body_element = $this->getDomDocument()->createElement($this->getOption('SuperParentName'));

        Arrays::eachWalk(
            $variables,
            function ($value, $key) use (&$body_element) {
                $this->appendElementTo($body_element, $key, $value);
            }
        );

        return $body_element;
    }

    /**
     * 指定した要素に新しい要素を子として追加します。
     *
     * @param \DOMElement $parent_elem 親となる要素のインスタンス
     * @param String|Integer $name     新しい要素の名前
     * @param mixed $value             新しい要素の値
     */
    private function appendElementTo(\DOMElement $parent_elem, $name, $value)
    {
        $element = null;

        if (String::isValid($value)) {
            $element = $this->getDomDocument()->createElement($name);
            $element->appendChild($this->getDomDocument()->createTextNode($value));
        } elseif (Arrays::isValid($value)) {
            $element = $this->getDomDocument()->createElement($name);
            $this->appendListElements($element, $value);
        }

        is_null($element) || $parent_elem->appendChild($element);
    }

    /**
     * 指定した要素に新しい要素を階層構造にして追加します。
     *
     * @param \DOMElement $element 親となる要素のインスタンス
     * @param Array $list          階層構造となる要素の元となるデータ配列
     */
    private function appendListElements(\DOMElement $element, array $list)
    {
        Arrays::eachWalk(
            $list,
            function ($value, $key) use (&$element) {
                $key_name = null;

                if (Arrays::isValid($value)) {
                    $key_name = String::isValid($key) ? $key : $this->getOption('DefaultListItemName');
                } elseif (String::isValid($value)) {
                    $key_name = $key;
                }

                is_null($key_name) || $this->appendElementTo($element, $key_name, $value);
            }
        );
    }
}

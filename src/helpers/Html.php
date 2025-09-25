<?php

namespace simialbi\yii2\kanban\helpers;

use yii\helpers\ArrayHelper;
use yii\helpers\Url;

class Html extends \yii\bootstrap5\Html
{
    /**
     * @var array|string[] $allowedTags
     * Default allowed tags for the stripTags method
     *
     * These will be merged with the stripTags method's $allowedTags parameter
     *
     * @see static::stripTags()
     * @see UnsetArrayValue
     * @see ReplaceArrayValue
     */
    public static array $allowedTags = ['b', 'br', 'em', 'hr', 'i', 'li', 'ol', 'p', 'strong', 'sub', 'sup', 'u', 'ul'];

    /**
     * Generates buttons and shows only the icons on mobile, or a dropdown if $dropdown is true
     *
     * @param array $items Array with buttons. each item can have the following array-keys:
     * - url (mandatory)
     * - icon [string|Closure]
     * - label
     * - options
     * - iconClass
     * @param bool $alwaysShowIcon if set to false, icon is not shown when label is shown
     * @param bool $dropdown If true, a dropdown with the links will be generated and shown on mobile
     * @param bool $autoConvert If this parameter is true, the dropdown will be disabled if only 1 item is provided
     * @param string $breakpoint Bootstrap breakpoint when to switch to the mobile version
     * @param bool $btnGroup If true, buttons will be rendered in a button group. static::btnGroupOptions can be defined
     * @param array $options Various options
     *
     * @return string
     * @throws \Exception
     */
    public static function responsiveButtons(
        array  $items,
        bool   $alwaysShowIcon = true,
        bool   $dropdown = false,
        bool   $autoConvert = true,
        string $breakpoint = 'lg',
        bool   $btnGroup = false,
        array  $options = ['btnGroupOptions' => ['class' => ['btn-group']]]
    ): string
    {
        $ret = '';

        if (count($items) === 1 && $autoConvert) {
            $dropdown = false;
        }

        if ($btnGroup) {
            $ret .= static::beginTag('div', ArrayHelper::getValue($options, 'btnGroupOptions'));
        }

        foreach ($items as $key => $item) {
            if ($key !== 0 && !$btnGroup) {
                $spanOptions = [];
                if ($dropdown) {
                    static::addCssClass($spanOptions, ['d-none', 'd-' . $breakpoint . '-inline-block']);
                }
                $ret .= static::tag('span', '&nbsp;', $spanOptions);
            }

            if (!is_array($item)) {
                $ret .= $item;
                continue;
            }

            $icon = ArrayHelper::getValue($item, 'icon', '');
            $label = ArrayHelper::getValue($item, 'label', '');
            $itemOptions = ArrayHelper::getValue($item, 'options', []);
            $iconClass = ArrayHelper::getValue($item, 'iconClass', 'rmrevin\yii\fontawesome\FAS');

            $labelOptions = ['class' => ['d-none', 'd-' . $breakpoint . '-inline-block']];


            if ($dropdown) {
                static::addCssClass($itemOptions, ['d-none', 'd-' . $breakpoint . '-inline-block']);
            }

            if ($icon instanceof \Closure) {
                $icon = $icon();
            } elseif ($icon !== '') {
                $iconOptions = $alwaysShowIcon ? [] : ['class' => ['d-' . $breakpoint . '-none']];
                $icon = ($iconClass)::i($icon, $iconOptions);
            }

            if ($label !== '') {
                // Do not hide label if dropdown is disabled and the icon is not provided
                if (!$dropdown && $icon == '') {
                    $labelOptions = [];
                }
                $label = ($icon !== '' ? ' ' : '') . static::tag('span', $label, $labelOptions);
            }

            $ret .= static::a($icon . $label, Url::to($item['url']), $itemOptions);
        }

        if ($btnGroup) {
            $ret .= static::endTag('div');
        }

        if ($dropdown) {
            $dropdownOptions = ArrayHelper::getValue($options, 'dropdownOptions');
            $dropdownOptions = ArrayHelper::merge(
                $dropdownOptions,
                ['class' => ['dropdown', 'd-flex', 'd-' . $breakpoint . '-none']]
            );

            $dropdownButtonOptions = ArrayHelper::getValue(
                $options,
                'dropdownButtonOptions',
                ['class' => ['btn', 'btn-primary', 'dropdown-toggle']]
            );
            $dropdownButtonOptions = ArrayHelper::merge(
                $dropdownButtonOptions,
                ['data-bs-toggle' => 'dropdown', 'aria' => ['haspopup' => 'true', 'expanded' => 'false']]
            );

            $dropdownButtonText = ArrayHelper::remove($dropdownButtonOptions, 'text', '');

            $dropdownMenuOptions = ArrayHelper::getValue($options, 'dropdownMenuOptions');
            $dropdownMenuOptions = ArrayHelper::merge(
                $dropdownMenuOptions,
                ['class' => ['dropdown-menu']]
            );

            $ret .= static::beginTag('div', $dropdownOptions);
            $ret .= static::button($dropdownButtonText, $dropdownButtonOptions);
            $ret .= static::beginTag('div', $dropdownMenuOptions);

            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $label = ArrayHelper::getValue($item, 'label', '');
                $options = ArrayHelper::getValue($item, 'options', []);

                static::addCssClass($options, ['dropdown-item']);
                static::addCssStyle($options, ['box-shadow' => 'none']);
                $ret .= static::a($label, Url::to($item['url']), $options);
            }

            $ret .= static::endTag('div');
            $ret .= static::endTag('div');
        }

        return $ret;
    }

    /**
     * Strip HTML and PHP tags from a string
     *
     * @param string $text
     * @param string|string[] $allowedTags
     *
     * @return string
     */
    public static function stripTags(string $text, array|string $allowedTags = []): string
    {
        $allowedTags = ArrayHelper::merge(static::$allowedTags, (array)$allowedTags);

        return strip_tags($text, $allowedTags);
    }
}

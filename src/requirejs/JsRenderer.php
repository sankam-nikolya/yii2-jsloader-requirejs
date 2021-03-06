<?php
/**
 * @copyright Copyright (c) 2016 Roman Ishchenko
 * @license https://github.com/ischenko/yii2-jsloader-requirejs/blob/master/LICENSE
 * @link https://github.com/ischenko/yii2-jsloader-requirejs#readme
 */

namespace ischenko\yii2\jsloader\requirejs;

use ischenko\yii2\jsloader\helpers\JsExpression;

/**
 * Implementation of a JsRenderer for RequireJS
 *
 * @author Roman Ishchenko <roman@ishchenko.ck.ua>
 * @since 1.0
 */
class JsRenderer implements \ischenko\yii2\jsloader\JsRendererInterface
{
    /**
     * Performs rendering of js expression
     *
     * @param JsExpression $expression
     * @return string
     */
    public function renderJsExpression(JsExpression $expression)
    {
        if (!$expression->getExpression()
            && !$expression->getDependencies()
        ) {
            return '';
        }

        if (($code = $expression->getExpression()) instanceof JsExpression) {
            $code = $this->renderJsExpression($code);
        }

        list($modules, $injects) = $this->extractRequireJsModules($expression->getDependencies());

        return $this->renderRequireJsCode($code, $modules, $injects);
    }

    /**
     * Performs rendering of requirejs code block
     *
     * @param string $code
     * @param array $modules
     * @param array $injects
     * @return string
     */
    private function renderRequireJsCode($code, array $modules, array $injects)
    {
        if ($modules === []) {
            return $code;
        }

        $requireBlock = 'require([' . implode(',', $modules) . ']';

        if (!empty($code)) {
            $requireBlock .= ', function(' . implode(',', $injects) . ") {\n{$code}\n}";
        }

        return $requireBlock . ');';
    }

    /**
     * @param Module[] $dependencies
     *
     * @return array
     */
    private function extractRequireJsModules(array $dependencies)
    {
        $pad = 0;
        $injects = [];
        $modules = [];

        /** @var Module $dependency */
        foreach ($dependencies as $dependency) {
            if ($dependency->getFiles() === []) {
                continue;
            }

            if (($inject = $dependency->getExports()) !== null) {
                if ($pad > 0) {
                    for ($i = 0; $i < $pad; $i++) {
                        $injects[] = 'undefined';
                    }
                }

                $pad = -1;
                $injects[] = $inject;
            }

            $pad++;
            $modules[] = json_encode($dependency->getAlias());
        }

        return [$modules, $injects];
    }
}

<?php
/**
 * @package     jelix
 * @subpackage  jtpl_plugin
 *
 * @author      Laurent Jouanneau
 * @copyright   2018-2020 Laurent Jouanneau
 *
 * @see        https://jelix.org
 * @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
 */

/**
 * a special if block to test easily the current control value
 * TO BE USED inside a {formcontrols} block.
 *
 * {ifctrl_value 'name', 'expected-value'} some tpl {else} some other tpl {/ifctrl_value}
 *
 * @param jTplCompiler $compiler the template compiler
 * @param bool         $begin    true if it is the begin of block, else false
 * @param array        $params   0=>'name',etc. to match against current control name and expected value
 *
 * @return string the php code corresponding to the begin or end of the block
 */
function jtpl_block_html_ifctrl_value($compiler, $begin, $params = array())
{
    if ($begin) {
        if (count($params) == 0) {
            $content = '';
            $compiler->doError1('errors.tplplugin.block.bad.argument.number', 'ifctrl_value', '1+');
        } elseif (count($params) == 1) {
            $content = ' if($t->_privateVars[\'__formTplController\']->isControlValueEqualsTo('.$params[0].')):';
        } else {
            $content = ' if($t->_privateVars[\'__formTplController\']->isControlValueEqualsTo('.$params[1].','.$params[0].')):';
        }
    } else {
        $content = ' endif; ';
    }

    return $content;
}

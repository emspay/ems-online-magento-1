<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

class EMS_Payment_Block_Adminhtml_Render_Heading extends Mage_Adminhtml_Block_Abstract
    implements Varien_Data_Form_Element_Renderer_Interface
{

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        return sprintf(
            '<tr class="system-fieldset-sub-head" id="row_%s">
                <td colspan="5">
                    <h4 id="%s">Version: %s</h4>
                    <div class="comment">
                        <span>%s</span>
                    </div>
                </td>
            </tr>',
            $element->getHtmlId(),
            $element->getHtmlId(),
            Mage::getConfig()->getNode('modules')->children()->EMS_Payment->version,
            $element->getComment()
        );
    }
}

<?php
/**
 * @version $Id$
 * @copyright Center for History and New Media, 2008-2011
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package CsvImport
 */

/**
 * The form on csv-import/index/map-columns.
 *
 * @package CsvImport
 * @author CHNM
 * @copyright Center for History and New Media, 2008-2011
 */
class CsvImport_Form_Mapping extends Omeka_Form
{
    private $_file;
    private $_itemTypeId;

    public function init()
    {
        parent::init();
        $this->setAttrib('id', 'csvimport-mapping');
        $this->setMethod('post'); 

        $elementsByElementSetName = 
            csv_import_get_elements_by_element_set_name($this->itemTypeId);
        array_unshift($elementsByElementSetName, 'Select Below');
        foreach ($this->_file->getColumnNames() as $index => $colName) {
            $rowSubForm = new Zend_Form_SubForm();
            $rowSubForm->addElement('select',
                'element',
                array(
                    'class' => 'map-element',
                    'multiOptions' => $elementsByElementSetName,
                )
            );
            $rowSubForm->addElement('checkbox', 'html');
            $rowSubForm->addElement('checkbox', 'tags');
            $rowSubForm->addElement('checkbox', 'file');
            $this->_setSubFormDecorators($rowSubForm);
            $this->addSubForm($rowSubForm, "row$index");
        }

        $this->addElement('submit', 'submit',
            array('label' => 'Import CSV File',
                  'class' => 'submit submit-medium'));
    }

    public function loadDefaultDecorators()
    {
        $this->setDecorators(array(
            array('ViewScript', array(
                'viewScript' => 'index/map-columns-form.php',
                'file' => $this->_file,
                'itemTypeId' => $this->_itemTypeId,
                'form' => $this,
            )),
        ));
    }

    public function setFile(CsvImport_File $file)
    {
        $this->_file = $file;
    }

    public function setItemTypeId($itemTypeId)
    {
        $this->_itemTypeId = $itemTypeId;
    }

    public function getMappings()
    {
        $columnMaps = array();
        $colCount = $this->_file->getColumnCount();
        for($i = 0; $i < $colCount; $i++) {
            if ($map = $this->getColumnMap($i)) {
                $columnMaps[] = $map;
            }
        }           
        return $columnMaps;
    }

    private function isTagMapped($index)
    {
        return $this->getSubForm("row$index")->tags->isChecked();
    }

    private function isFileMapped($index)
    {
        return $this->getSubForm("row$index")->file->isChecked();
    }

    private function getMappedElementId($index)
    {
        return $this->_getRowValue($index, 'element');
    }

    private function _getRowValue($row, $name)
    {
        return $this->getSubForm("row$row")->$name->getValue();
    }

    private function _setSubFormDecorators($subForm)
    {
        // Get rid of the fieldset tag that wraps subforms by default.
        $subForm->setDecorators(array(
            'FormElements',
        ));

        // Each subform is a row in the table.
        foreach ($subForm->getElements() as $el) {
            $el->setDecorators(array(
                array('decorator' => 'ViewHelper'),
                array('decorator' => 'HtmlTag',
                      'options' => array('tag' => 'td')),
            ));
        }
    }

    /**
     * @internal It's unclear whether the original behavior allowed a row to 
     * represent a tag, a file, and an HTML element text at the same time.  If 
     * so, that behavior is weird and buggy and it's going away until deemed 
     * otherwise.
     */
    private function getColumnMap($index)
    {
        $columnMap = null;
        if ($this->isTagMapped($index)) {
            $columnMap = new CsvImport_ColumnMap($index, 
                CsvImport_ColumnMap::TARGET_TYPE_TAG);
        } else if ($this->isFileMapped($index)) {
            $columnMap = new CsvImport_ColumnMap($index, 
                CsvImport_ColumnMap::TARGET_TYPE_FILE);
        } else if ($elementId = $this->getMappedElementId($index)) {
            $columnMap = new CsvImport_ColumnMap($index, 
                CsvImport_ColumnMap::TARGET_TYPE_ELEMENT);
            $columnMap->addElementId($elementId);
            $columnMap->setDataIsHtml($this->_getRowValue($index, 'html'));
        }
        return $columnMap;
    }
}
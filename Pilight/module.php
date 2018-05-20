<?php

define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__ . '/libs/helpers/autoload.php');

/**
 * Class Pilight
 * IP-Symcon pilight module
 *
 * @version     1.1
 * @category    Symcon
 * @package     de.codeking.symcon.pilight
 * @author      Frank Herrmann <frank@codeking.de>
 * @link        https://codeking.de
 * @link        https://github.com/CodeKing/de.codeking.symcon.pilight
 *
 */
class Pilight extends Module
{
    use InstanceHelper;

    const guid_parent = '{041619E4-E69D-4C05-AD95-904BA3D45942}';
    const guid_send = '{F9ACF9F1-F7EA-4C39-8C28-95AA966C5672}';

    public $data = [];
    public $devices = [];

    /**
     * create instance
     */
    public function Create()
    {
        parent::Create();

        // connect parent i/o device
        $this->ConnectParent(self::guid_parent);

        // register private properties
        $this->RegisterPropertyString('Identifier', '');
    }

    /**
     * execute, when kernel is ready
     */
    protected function onKernelReady()
    {
        // enable action on variables
        if ($ident = $this->_getIdentifierByNeedle('State')) {
            $this->force_ident = true;
            $this->EnableAction($ident[0]);
        }
    }

    /**
     * Switch device on / off
     * @param bool $Value
     * @return bool|void
     */
    public function SwitchMode(bool $Value)
    {
        $Ident = $this->identifier($this->InstanceID . '_State');
        $this->RequestAction($Ident, $Value);
    }

    /**
     * Dim device
     * @param int $Value
     * @return bool|void
     */
    public function Dim(int $Value)
    {
        $Ident = $this->identifier($this->InstanceID . '_State');
        $this->RequestAction($Ident, $Value);
    }

    /**
     * Receive and update current data
     * @param string $JSONString
     * @return bool|void
     */
    public function ReceiveData($JSONString)
    {
        // convert json data to array
        $data = json_decode($JSONString, true);

        // get current device by identifier
        $identifier = $this->ReadPropertyString('Identifier');
        if (isset($data['Devices'][$identifier])) {
            // update variables
            foreach ($data['Devices'][$identifier] AS $key => $value) {
                if ($ident = $this->_getIdentifierByNeedle($key)) {
                    SetValue($this->GetIDForIdent($ident[0]), $value);
                }
            }
        }
    }

    /**
     * Request Actions
     * @param string $Ident
     * @param $Value
     * @return bool|void
     */
    public function RequestAction($Ident, $Value)
    {
        // update variable
        SetValue($this->GetIDForIdent($Ident), $Value);

        // build data
        $data = [
            'DataID' => self::guid_send,
            'Device' => $this->ReadPropertyString('Identifier'),
            'Value' => $Value
        ];

        // send data to parent
        $this->SendDataToParent(json_encode($data));
    }
}
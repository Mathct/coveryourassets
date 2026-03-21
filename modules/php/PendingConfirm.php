<?php

namespace Bga\Games\coveryourassets;   // ATTENTION NOM DU JEU

trait PendingConfirmTrait  // ATTENTION
{
    public function argConfirm($parg1, $parg2)
    {
        $ret = [];
        $ret["selectable"] = [];
        $ret["selected"] = [];
        $ret['buttons'] = [];
        $ret["function"] = "ChooseAction";
        $ret['title'] = clienttranslate('${actplayer} must confirm');
        $ret['titleyou'] = clienttranslate('${you} must confirm');

        
        $ret['buttons'][] = 'yes_btn';
        $ret['buttons'][] = 'no_btn';
        

        return $ret;
    }

    public function Confirm($parg1, $parg2, $varg1, $varg2)
    {
        
    }

        

    
}
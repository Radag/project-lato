<?php

namespace App\Helpers;

class HelpersList
{


    public static function timeDifferceText($difference)
    {
        $return = "";
  
        if($difference->invert) {
            if ($difference->y > 0) {
                $return = '<div class="remaining-time important">' . $difference->format('před %y lety') . '</div>';
            } elseif ($difference->m > 0) {
                $return = '<div class="remaining-time important">' . $difference->format('před %m měsíci') . '</div>';           
            } elseif ($difference->d > 0) {
                $return = '<div class="remaining-time important">' . $difference->format('před %d dny') . '</div>';          
            } elseif ($difference->h > 0) {
                $return = '<div class="remaining-time important">' . $difference->format('před %h hodinami') . '</div>';           
            } elseif ($difference->i > 0) {
                $return = '<div class="remaining-time important">' . $difference->format('před %i minutami') . '</div>';          
            }
        } else {
            if ($difference->y > 0) {
                $return = '<div class="remaining-time">' . $difference->format('%y let') . '</div>';
            } elseif ($difference->m > 0) {
                $return = '<div class="remaining-time">' . $difference->format('%m měsíců') . '</div>';           
            } elseif ($difference->d > 0) {
                $return = '<div class="remaining-time">' . $difference->format('%d dní') . '</div>';          
            } elseif ($difference->h > 0) {
                $return = '<div class="remaining-time important">' . $difference->format('%h hodin') . '</div>';           
            } elseif ($difference->i > 0) {
                $return = '<div class="remaining-time important">' . $difference->format('%i minut') . '</div>';          
            }
        }
        
                
        return $return;
    }
}
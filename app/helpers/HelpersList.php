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
    
	public static function getWordCounting($number, $words, $withNumber = true) 
	{
		$word = "";
		if($number == 1) {
			$word = $words[0];
		} elseif($number < 5) {
			$word = $words[1];
		} else {
			$word = isset($words[2]) ? $words[2] : $words[1];
		}
		if($withNumber) {
			return $number . " " . $word;
		} else {
			return $word;
		}
	}	
	
    public static function attachTypeIco($type)
    {
        if($type === 'image') {
            $icon = 'image';
        } elseif ($type === 'document') {
            $icon = 'document';
        } elseif($type === 'spreadsheet') {
            $icon = 'spreadsheet';
        } elseif($type === 'presentation') {
            $icon = 'unknown';
        } else {
            $icon = 'unknown';
        }
        
        if($type === 'image') {
            $iconName = 'image';
        } elseif ($type === 'document') {
            $iconName = 'description';
        } elseif($type === 'spreadsheet') {
            $iconName = 'equalizer';
        } elseif($type === 'presentation') {
            $iconName = 'slideshow';
        } else {
            $iconName = 'attachment';
        }
        
        $return = '<div class="file-icon ' . $icon . '">
                        <i class="material-icons">
                            ' . $iconName . '
                        </i>
                    </div>';
        return $return;
    }
    
    public static function inputErrors($input) : String
    {
        if($input->getName() === "form") {
            $errors = $input->getOwnErrors();
        } else {
            $errors = $input->getErrors();
        }
        if(!empty($errors)) {
            $errorsHtml = "";
            foreach($errors as $error) {
                $errorsHtml .= '<div class="error" >' . $error . '</div>';
            }
            return "<div class='errors'>" . $errorsHtml . "</div>";
        }
        return "";
    }
}
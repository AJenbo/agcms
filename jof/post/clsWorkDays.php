<?php 

/* 

  i-Bridge Systems Software Solutions 
  http://www.ibridge.co.uk 

  Copyright (c) 2006 i-Bridge Systems & www.ibridge.co.uk 
  for more information, products or support contact simon AT ibridge.co.uk or support AT ibridge . co . uk 

*/ 

class clsWorkDays { 

// 
//Class    : Hold all the functions to help when calculating dates 
//Params 
//         : 
// 

	var $holidays = array(); 
	var $holiday_dates = array(); 
	var $seconds_per_day = 86400; 
	var $sunday_val      = "0"; 
	var $saturday_val    = "6"; 

    function clsWorkDays( $p_type='DK' ) { 
     
        // 
        //Function : constructor to automaticaly set the list of holidays. 
        //Params 
        //         : p_type   this param can be used when adding other countrys holidays 
        // Returns 
        //         : sets of holidays 

        // 
        // future dates may be found from the same place i looked 
        // http://www.dti.gov.uk/employment/bank-public-holidays/index.html 
        // or for previous try 
        // http://www.dti.gov.uk/employment/bank-public-holidays/bank-public-holidays/page18893.html 
        // 


        // 

        if ( $p_type=='DK' ) { 
         	//faste helig dage der ikke falder i weekenden
            $this->holidays[] = new Holiday("Nytårsdag", $this->get_timestamp(date('Y').'-01-01')); 
            $this->holidays[] = new Holiday("Grundlovsdag", $this->get_timestamp(date('Y').'-06-05')); 
            $this->holidays[] = new Holiday("Juleaften", $this->get_timestamp(date('Y').'-12-24'));
            $this->holidays[] = new Holiday("Juledag", $this->get_timestamp(date('Y').'-12-25'));
            $this->holidays[] = new Holiday("2. Juledag", $this->get_timestamp(date('Y').'-12-26'));
			
			//faste helig dage der ikke faler i weekenden sidste år
            $this->holidays[] = new Holiday("Nytårsdag", $this->get_timestamp((date('Y')-1).'-01-01')); 
            $this->holidays[] = new Holiday("Grundlovsdag", $this->get_timestamp((date('Y')-1).'-06-05')); 
            $this->holidays[] = new Holiday("Juleaften", $this->get_timestamp((date('Y')-1).'-12-24'));
            $this->holidays[] = new Holiday("Juledag", $this->get_timestamp((date('Y')-1).'-12-25'));
            $this->holidays[] = new Holiday("2. Juledag", $this->get_timestamp((date('Y')-1).'-12-26'));
			
			//Heligdage der varier afhængit af påske
			$easter = easter_date(date('Y'));
            $this->holidays[] = new Holiday("Skærtorsdag", $easter-172801); 
            $this->holidays[] = new Holiday("Langfredag", $easter-86401); 
            $this->holidays[] = new Holiday("2. Påskedag", $easter+86400); 
            $this->holidays[] = new Holiday("Store Bededag", $easter+2246400); 
            $this->holidays[] = new Holiday("Kr. Himmelfart", $easter+3369600); 
            $this->holidays[] = new Holiday("Pinsedag", $easter+4233600); 
            $this->holidays[] = new Holiday("2. Pinsedag", $easter+4320000); 
			
			//Heligdage der varier afhængit af påske sidste år
			$easter = easter_date(date('Y')-1);
            $this->holidays[] = new Holiday("Skærtorsdag", $easter-172801); 
            $this->holidays[] = new Holiday("Langfredag", $easter-86401); 
            $this->holidays[] = new Holiday("2. Påskedag", $easter+86400); 
            $this->holidays[] = new Holiday("Store Bededag", $easter+2246400); 
            $this->holidays[] = new Holiday("Kr. Himmelfart", $easter+3369600); 
            $this->holidays[] = new Holiday("Pinsedag", $easter+4233600); 
            $this->holidays[] = new Holiday("2. Pinsedag", $easter+4320000); 
        } 



        //go fill array to enable fast searches 
        foreach ( $this->holidays as $holiday_date ) { 
            $this->holiday_dates[] = $holiday_date->date; 
        } 

    } 

    function days_diff($p_start_date, $p_end_date = NULL, $p_workdays_only = TRUE, $p_skip_holidays = TRUE){ 
     
    // 
    //Function : Main function to calculate the number of days or work days between 2 dates. If no end date passed 
    //           in then this can be used to identify if the day is a working day as the function will return 1 or 0 
    //Params 
    //         : p_start_date     This paramter is the range starting date 
    //         : p_end_date       This paramter is the range ending date (can be null) 
    //         : p_workdays_only  This paramter is set if you DO NOT want to count weekends 
    //         : p_skip_holidays  This paramter is set if you DO NOT want to count standard Bank Holidays & enforced business shutdowns 
    // Returns 
    //         : number of days between the 2 dates or if no end date passed in then 1/0 if the start day is a work day 
    // 
     
        $end_date        = $p_end_date; 
        if ( strlen($p_end_date)==0 ) $end_date = $p_start_date; 
     
        $end_date        = strtotime($end_date); 
        $start_date      = strtotime($p_start_date); 
        $nbr_work_days   = 0; 

        for($day_val = $start_date; $day_val <= $end_date; $day_val += $this->seconds_per_day){ 
            $pointer_day = date("w", $day_val); 
            if($p_workdays_only == true){ 
                if(($pointer_day != $this->sunday_val) AND ($pointer_day != $this->saturday_val)){ 
                    if($p_skip_holidays == true){ 
                        if(!in_array($day_val, $this->holiday_dates)){ 
                            $nbr_work_days++; 
                        } 
                    }else{ 
                        $nbr_work_days++; 
                    } 
                } 
            }else{ 
                if($p_skip_holidays == true){ 
                    if(!in_array($day_val, $this->holiday_dates)){ 
                        $nbr_work_days++; 
                    } 
                }else{ 
                    $nbr_work_days++; 
                } 
            } 
        } 
        return $nbr_work_days; 
    } 


    function get_timestamp($p_date){ 
     
    // 
    //Function : internal function to convert a date from mySQL fmt to unix timestamp 
    //Params 
    //         : p_date     This paramter takes a date in the format yyyy-mm-dd 
    // Returns 
    //         : a unix timestamp 
    // 
     
        $date_array = explode("-",$p_date); // split the array 
     
        $the_year = $date_array[0]; 
        $the_month = $date_array[1]; 
        $the_day = $date_array[2]; 
     
        $the_timestamp = mktime(0,0,0,$the_month,$the_day,$the_year); 
        return($the_timestamp); // return it to the user 
    } 

} 



class Holiday{ 

// 
//Class    : Create and hold the list of bank holidays and enforced business shutdowns 
//Params 
//         : 
// Returns 
//         : array of holidays 
// 

    public $name; 
    public $date; 

    //constructor to automaticaly define the details of each holiday as it is created. 
    function holiday($name, $date){ 
        $this->name   = $name;   // Holiday title 
        $this->date   = $date;   // Timestamp of date 
    } 
} 

?> 
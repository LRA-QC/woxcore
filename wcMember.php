<?php

//! Members class
/*!
	The wcMember class handle all members actions (registration, modification, ...)
*/
class wcMember
{
    ///register the user in the system
    static function register($data)
    {
        /*
         * 0= success
         * 1= invalid data is passed, should be an array
         * 2= nickname is too short
         * 3= email address seems invalid
         * 4= password doesnt match
         * 5= unknown error
         * 6= cannot insert member into table because of index violation (like a duplicate member using already existing email address)
         * 7= email blank
         * 8= password1 is blank
         * 9= password2 is blank
         * 10=nickname or email already in use
         */

        if (is_array($data) == false)
            return 1;

        //empty local variables
        $password = $password1 = $password2='';
        $nickname = $email = '';

        //parse supplied array for specific keys
        foreach( $data as $key => $value)
        {
            switch ($key)
            {
                case 'nickname':                //extract nickname
                    $nickname = htmlspecialchars( trim( strip_tags( $value ) ) );
                    if (strlen ($nickname) < 2 )
                        return 2;
                    break;
                case 'email':                   //extract email
                    $value = htmlspecialchars(  filter_var(strip_tags($value), FILTER_VALIDATE_EMAIL)); //PHP53: sanitize string
                    if ($value === false)   //exit on error
                        return 3;
                    $email = trim ($value) ;    //trim variables of spaces
                    break;
                case 'password1':               //extract password1
                    $password1=trim($value);
                    break;
                case 'password2':               //extract password2
                    $password2=trim($value);
                    break;
                case 'language':                //extract language
                    $language=trim($value);
                    break;
                default:
                   //echo $key.' -- '.$value.'<br />';
                    break;
            }

        }
      

        //exit if email is blank
        if (strlen($email) == 0)
            return 7;

        //exit if password1 is blank
        if (strlen($password1) == 0)
            return 8;

        //exit if password1 is blank
        if (strlen($password2) == 0)
            return 9;

        //if passwords don't match, return an error
        if ($password1 == $password2)
            $password = $password1;
        else
            return 4;

        //TODO: should validate in the system settings if the registration process is allowed

        //no check are done for existing duplicate nickname or email because an unique index is already present
        //insterting a duplicate will fail and return an error
        $db = wcCore::getDatabaseHandle();
        $db->connect();

        //This will check if there is a member with same nickname or email address
        $condition = sprintf( "nickname = '%s' or email = '%s' " , $nickname, $email);
        $result = $db->querySelect('Members', $condition, 'count(*) as cnt');

        //on success, an array is returned
        if (is_array($result))  
        {
            $count = intval( $result[0]['cnt'] ) ;  //get number of existing rows matching
            if ( $count > 0)                        //already an entry, exiting with error
            {
                return 10;
            }
            else
            {   //create member
                $values = sprintf( "'%s','%s','%s','%s', '%s',now() ", $language, $nickname, $email, sha1($password), sha1(rand()) );
                $result = $db->queryInsert('Members', 'idLanguage, nickname, email, hash1, hash2 , dateCreated', $values);

                //TODO: Need to send email for validation, send ID + hash 2, create other field in table , make sure that account has ben validated in 48 hours
                //TODO: Need to do validation page

                //debug purpose, when new error
                //var_dump($result);
                if ($result==-1)
                {
                    switch ($db->dbHandle->errno)
                    {
                        case 1062:
                            return 6;
                        default:
                            return 5;
                            break;
                    }
                }
                else
                {
                    if ($result == 1)
                    {
                        return 0;
                    }
                }
            }
        }
        return 5;
    }
}
?>
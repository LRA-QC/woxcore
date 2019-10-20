<?php
//! Commenting system class
/*!
functions for managing comments
*/
class wcComment
{
    //!add a comment
    static function add( $idnode, $idfile,$author, $authoremail, $comment, $language)
    {
        if(filter_var( $authoremail, FILTER_VALIDATE_EMAIL) )
	    {
	     	$idnode = intval($idnode);
	     	$idfile = intval($idfile);

            if ( $idnode>= 0 )
            {
                if ( $idfile >= 0 )
                {
                    $db	= wcCore::getDatabaseHandle();
                    $temp = sprintf( "%d,%d,'%s','%s','%s','%s'" , $idnode, $idfile , htmlspecialchars ($author, ENT_QUOTES, 'UTF-8'), $authoremail , htmlspecialchars ($comment, ENT_QUOTES, 'UTF-8') , $language);
                    if ($db->queryInsert('Comments', 'idNode, idFile, author, email, comment, idLanguage', $temp)>0)
                    {
                        wcComment::updateNodeCommentsCount($idnode, $idfile);
                        return 0;
                    }
                }
                else
                {
                    return 4;
                }
            }
            else
            {
                return 3;
            }
	     }
		 else
         {
            return 2;
         }
		 return 1;
	}
    //!approve a comment
    static function approve($idcomment)
    {
    }

    //!disable a comment
    static function disable($idcomment)
    {
    }
		
    //!get a list of comments
    static function fetch( $language, $idnode, $idfile=0, $page=0, $pageentries=10 )
    {
        $db			= wcCore::getDatabaseHandle();
    $idnode 	= intval($idnode);
    $idfile 	= intval($idfile);

    //echo "node: $idnode, $idfile<br />";

        $language 	= filter_var($language, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

//      echo "language: $language<br />";

        if ( $idnode >= 0 )
        {
            if ( $idfile >= 0 )
            {
                $temp = sprintf( "idNode = %d and idFile = %d and idLanguage = '%s'" , $idnode, $idfile , $language );
//					echo "$temp<br />";
                return $db->querySelect('Comments', $temp);
            }
        }
        return null;
    }

    //!get a specific comment
    static function get($idcomment)
    {
    }
		
		
    //!delete a comment
    static function remove($idcomment)
    {
    }
		
    static function updateNodeCommentsCount($idnode, $idfile)
    {
        $result = 0;
        $db		  = wcCore::getDatabaseHandle();

        $idnode = intval($idnode);
        $idfile = intval($idfile);

        $where = sprintf('idNode = %d and idFile = %d  group by idNode,idFile', $idnode, $idfile);
        $nodes 	= $db->querySelect('Comments', $where, 'idNode, idFile, count(idNode) kc','1');
        $kn 	= count($nodes);

        if ($kn > 0 ) //only process if the previous query returned something
        {
            if ($idfile == 0) //update node comment count
            {
                        if ($db->queryUpdate('Nodes','countComment='.$nodes[0]['kc'], 'idNode = '.$nodes[0]['idNode']) > 0)
                         $result++;
            }
            else              //update a specific file comment count
            {
                        if ($db->queryUpdate('Files','countComment='.$nodes[0]['kc'], 'idNode = '.$nodes[0]['idNode']. ' and idFile='.$nodes[0]['idFile']) > 0)
                            $result++;
            }
        }
			return $result;
    }
		
    static function updateAllCommentsCount()
    {
        $db		= wcCore::getDatabaseHandle();
        $nodes 	= $db->querySelect('Comments', '1 group by idNode,idFile','idNode, idFile, count(idNode) kc');
        $kn 	= count($nodes);

        $result = 0;

        for ($k=0 ; $k < $kn; $k++)
        {
            if (intval($nodes[$k]['idFile'])==0)
            {
                if ($db->queryUpdate('Nodes','countComment='.$nodes[$k]['kc'], 'idNode = '.$nodes[$k]['idNode']) > 0)
                    $result++;
            }
            else
            {
                if ($db->queryUpdate('Files','countComment='.$nodes[$k]['kc'], 'idNode = '.$nodes[$k]['idNode']. ' and idFile='.$nodes[$k]['idFile']) > 0)
                    $result++;
            }
        }
        return $result;
    }
}
?>
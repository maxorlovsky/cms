<?php

class Log
{
    public static function save($db, $log = []) {
        if (isset($log['user_id'])) {
    		$userId = (int)$log['user_id'];
    	} else {
    		$userId = 0;
    	}
    	
    	if (!isset($log['module'])) {
    		$log['module'] = '';
        }
        $log['module'] = strtolower($log['module']);
    	
    	if (!isset($log['type'])) {
    		$log['type'] = '';
        }
        
        $q = $db->prepare('INSERT INTO `tm_logs` SET '.
            '`module` = :module, '.
            '`type` = :type, '.
            '`user_id` = :user_id, '.
            '`date` = NOW(), '.
            '`ip` = :ip, '.
            '`info` = :info '
        );
        $q->bindParam(':module', $log['module'], PDO::PARAM_STR);
        $q->bindParam(':type', $log['type'], PDO::PARAM_STR);
        $q->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $q->bindParam(':ip', $_SERVER['REMOTE_ADDR'], PDO::PARAM_STR);
        $q->bindParam(':info', $log['info'], PDO::PARAM_STR);
        $q->execute();
    }
}
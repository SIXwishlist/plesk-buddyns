<?php
/**
 * BuddyNS API Extension for Plesk
 * Written by Jerome Twell @ tetragroup.org
 * 
 * This script is added as --custom-backend for Plesk DNS in post-install.php
 * and removed in pre-uninstall.php respectively.
 */

pm_Loader::registerAutoload();
pm_Context::init('buddyns');


if (!pm_Settings::get('enabled')) {
    exit(0);
    /* Exit if checkbox 'enabled' in extension settings is not checked. */
}

// Grab frequently accessed vars here
$APIKey = pm_Settings::get('key');
$MasterIP = pm_Settings::get('IP');

function buddyNSCreateZone($zoneName,$zoneIP,$key)
    {
      /*
        Action: Create
        Create new zone. 
        Returns JSON Response
        */
        $zoneNameTrimmed = rtrim($zoneName,'.');
        
        $service_url = 'https://www.buddyns.com/api/v2/zone/';
        $curl = curl_init($service_url);
        $curl_post_data = array(
                'name' => $zoneNameTrimmed,
                'master' => $zoneIP,
        );

        /* Headers must be passed as elements in an array */
        $curl_header= array();
        $curl_header[] = 'Authorization: Token '.$key;

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $curl_post_data);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_header);

        $curl_response = curl_exec($curl); //Fire!

        if ($curl_response === false) {
            $info = curl_getinfo($curl);
            curl_close($curl); 
            return false;
        } else {
            curl_close($curl);
            return json_decode($curl_response);
        }

        
    }



function buddyNSSyncNow($zoneName,$key)
    {
        /*
        Action: Update
        Request SyncNow! for zone 
        Returns JSON response
        */

        /* We use rtrim here because the trailing dot(.) will not match the zone we're looking for */
            $service_url = 'https://www.buddyns.com/api/v2/sync/'.rtrim($zoneName,'.'); 

            /* Headers must be passed as elements in an array */
            $curl_header= array();
            $curl_header[] = 'Authorization: Token '.$key;

            $curl = curl_init($service_url);

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_header);


            $curl_response = curl_exec($curl); 

            if ($curl_response === false) {
                $info = curl_getinfo($curl);
                curl_close($curl); 
                return false;
            } else {
                curl_close($curl);
                return json_decode($curl_response);
            }
    }

function buddyNSDeleteZone($zoneName,$key)
    {
         /*
        Action: Delete
        Deletes zone.
        Returns JSON response
        */

        /* We use rtrim here because the trailing dot(.) will not match the zone we're looking for */
        $service_url = 'https://www.buddyns.com/api/v2/zone/'.rtrim($zoneName,'.'); 
        $curl = curl_init($service_url);

        $curl_header= array();
        $curl_header[] = 'Authorization: Token '.$key;

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_header);

        $curl_response = curl_exec($curl);

        if ($curl_response === false) {
            $info = curl_getinfo($curl);
            curl_close($curl); 
            return false;
        } else {
            curl_close($curl);
            return json_decode($curl_response);
        }


    }



/**
 * Read zone script from stdin
 *
 *[
 * {
 *  "command": "(update|delete)",
 *  "zone": {
 *      "name": "domain.tld.",
 *      "displayName": "domain.tld.",
 *      "soa": {
 *          "email": "email@address",
 *          "status": 0,
 *          "type": "master",
 *          "ttl": 86400,
 *          "refresh": 10800,
 *          "retry": 3600,
 *          "expire": 604800,
 *          "minimum": 10800,
 *          "serial": 123123123,
 *          "serial_format": "UNIXTIMESTAMP"
 *      },
 *      "rr": [{
 *          "host": "www.domain.tld.",
 *          "displayHost": "www.domain.tld.",
 *          "type": "CNAME",
 *          "displayValue": "domain.tld.",
 *          "opt": "",
 *          "value": "domain.tld."
 *      }]
 * }, {
 *  "command": "(createPTRs|deletePTRs)",
 *  "ptr": {
 *      "ip_address": "1.2.3.4",
 *      "hostname": "domain.tld"}
 * }
 *]
 */
$data = json_decode(file_get_contents('php://stdin'));
//Example:
//[
//    {"command": "update", "zone": {"name": "domain.tld.", "displayName": "domain.tld.", "soa": {"email": "amihailov@parallels.com", "status": 0, "type": "master", "ttl": 86400, "refresh": 10800, "retry": 3600, "expire": 604800, "minimum": 10800, "serial": 1363228965, "serial_format": "UNIXTIMESTAMP"}, "rr": [
//        {"host": "www.domain.tld.", "displayHost": "www.domain.tld.", "type": "CNAME", "displayValue": "domain.tld.", "opt": "", "value": "domain.tld."},
//        {"host": "1.2.3.4", "displayHost": "1.2.3.4", "type": "PTR", "displayValue": "domain.tld.", "opt": "24", "value": "domain.tld."},
//        {"host": "domain.tld.", "displayHost": "domain.tld.", "type": "TXT", "displayValue": "v=spf1 +a +mx -all", "opt": "", "value": "v=spf1 +a +mx -all"},
//        {"host": "ftp.domain.tld.", "displayHost": "ftp.domain.tld.", "type": "CNAME", "displayValue": "domain.tld.", "opt": "", "value": "domain.tld."},
//        {"host": "ipv4.domain.tld.", "displayHost": "ipv4.domain.tld.", "type": "A", "displayValue": "1.2.3.4", "opt": "", "value": "1.2.3.4"},
//        {"host": "mail.domain.tld.", "displayHost": "mail.domain.tld.", "type": "A", "displayValue": "1.2.3.4", "opt": "", "value": "1.2.3.4"},
//        {"host": "domain.tld.", "displayHost": "domain.tld.", "type": "MX", "displayValue": "mail.domain.tld.", "opt": "10", "value": "mail.domain.tld."},
//        {"host": "webmail.domain.tld.", "displayHost": "webmail.domain.tld.", "type": "A", "displayValue": "1.2.3.4", "opt": "", "value": "1.2.3.4"},
//        {"host": "domain.tld.", "displayHost": "domain.tld.", "type": "A", "displayValue": "1.2.3.4", "opt": "", "value": "1.2.3.4"},
//        {"host": "ns.domain.tld.", "displayHost": "ns.domain.tld.", "type": "A", "displayValue": "1.2.3.4", "opt": "", "value": "1.2.3.4"}
//    ]}},
//    {"command": "createPTRs", "ptr": {"ip_address": "1.2.3.4", "hostname": "domain.tld"}},
//    {"command": "createPTRs", "ptr": {"ip_address": "2002:5bcc:18fd:000c:0001:0002:0003:0004", "hostname": "domain.tld"}}
//]

foreach ($data as $record) {

    switch ($record->command) {

        case 'create':

            buddyNSCreateZone($record->zone->name,$MasterIP,$APIKey);

            break;

        case 'update':

            $sync_response = buddyNSSyncNow($record->zone->name,$APIKey); // SyncNow should return JSON response

            if($sync_response->detail == "Not found") { 
                buddyNSCreateZone($record->zone->name,$MasterIP,$APIKey); // Automatically add new zone if not found.
            } 

            break;

        case 'delete':

            buddyNSDeleteZone($record->zone->name,$APIKey);

            break;


    } 
}

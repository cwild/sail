<?php
//
// Developed by CoCo
// Copyright (C) 2012 CoCoSoft
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public Licenseinterfaces
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//


Class sarkfreset {
	
	protected $message; 
	protected $myPanel;
	protected $dbh;
	protected $helper;
	protected $validator;
	protected $invalidForm;
	protected $error_hash = array();
	protected $log = NULL;
	protected $live = false;
	protected $reboot;
	
public function showForm() {
	
	$this->myPanel = new page;
	$this->dbh = DB::getInstance();
	$this->helper = new helper;
	$login = false;
	
	echo '<form id="sarkfresetForm" action="' . $_SERVER['PHP_SELF'] . '" method="post">' . PHP_EOL;
	
	$this->myPanel->pagename = 'Factory Reset';
	
	if (!empty( $_POST['password'] )) {
		if ($this->helper->checkCreds( "admin",$_POST['password'],$this->message,$login )) {
			$this->doReset();
			$this->message = "Rebooting now (IP may change)";
			$this->helper->request_syscmd ("reboot");
			$this->reboot=true;
		}		
	}
	else {
//		$this->message = "Enter administrator password to execute";
	}


	$this->showMain();
	
	$this->dbh = NULL;
	return;
	
}
	
private function showMain() {

	if (isset($this->message)) {
		$this->myPanel->msg = $this->message;
	} 
	
	$this->myPanel->Heading();
	if (isset($this->message)) {
		foreach ($this->error_hash as $inpname => $inp_err) {
			echo "<p>$inpname : $inp_err</p>\n";
		}       
	}
	
	if ($this->reboot) {
		echo '<div class="messagebox" >';
		echo '<div class="message" style="font-size: 2em;padding-left:10em;padding-top:2em;">';
		echo $this->log;
		echo '</div>';
		echo '</div>';
		return;
	}		

    echo '<div class="datadivtabedit">';

	echo '<div id="reset" >'. PHP_EOL;
	
	echo '<h2>'. PHP_EOL;
	echo '<input id="resetdb" type="checkbox" name="resetdb" checked="checked" >'. PHP_EOL;
	echo ' :Reset PBX Database?';
	echo '<br/>';
	echo '<input id="backups" type="checkbox" name="backups" checked="checked" >'. PHP_EOL;
	echo ' :Delete backups?';				
	echo '<br/>';
	echo '<input id="snaps" type="checkbox" name="snaps" checked="checked" >'. PHP_EOL;
	echo ' :Delete snapshots?';				
	echo '<br/>';
	echo '<input id="usergreets" type="checkbox" name="usergreets" checked="checked" >'. PHP_EOL;
	echo ' :Delete greetings?';				
	echo '<br/>';
	echo '<input id="vmail" type="checkbox" name="vmail" checked="checked" >'. PHP_EOL;
	echo ' :Delete voicemail?';				
	echo '<br/>';
	echo '<input id="vrec" type="checkbox" name="vrec" checked="checked" >'. PHP_EOL;
	echo ' :Delete recordings?';				
	echo '<br/>';		
	echo '<input id="cdrs" type="checkbox" name="cdrs" checked="checked" >'. PHP_EOL;
	echo ' :Delete CDRs?';				
	echo '<br/>';
	echo '<input id="logs" type="checkbox" name="logs" checked="checked" >'. PHP_EOL;
	echo ' :Delete logs?';				
	echo '<br/>';
	echo '<input id="firewall" type="checkbox" name="firewall" checked="checked" >'. PHP_EOL;
	echo ' :Reset firewall rules to default?';				
	echo '<br/>';
	echo '<input id="dhcp" type="checkbox" name="dhcp" checked="checked" >'. PHP_EOL;
	echo ' :Reset network to defaults (DHCP)?';				
	echo '<br/>';	
	echo '<input id="host" type="checkbox" name="host" checked="checked" >'. PHP_EOL;
	echo ' :Reset hostname and domain to default?';				
	echo '<br/>';		
	echo '<input id="sshport" type="checkbox" name="sshport" checked="checked" >'. PHP_EOL;
	echo ' :Reset ssh port to default?';				
	echo '<br/>';	
	echo '<input id="recs" type="checkbox" name="recs" checked="checked" >'. PHP_EOL;
	echo ' :Delete voice recordings?';				
	echo '<br/>';
	echo '<input id="ldap" type="checkbox" name="ldap" checked="checked" >'. PHP_EOL;
	echo ' :Delete LDAP directory entries?';				
	echo '<br/>';																													
	echo '</h2>'. PHP_EOL;

    echo '</div>' . PHP_EOL;
    
    echo '<br/><br/>';
    echo '<div id="container">' . PHP_EOL;
    echo '<input type="password" id="password" name="password" placeholder="Enter Admin Password"> ' . PHP_EOL;       
    echo '<div id="lower"> ' . PHP_EOL;   	
    echo '</div>' . PHP_EOL; 
    echo '<br/><br/>';
    echo '<input type="submit" value="RESET"> '. PHP_EOL;                     

    echo '</div>' . PHP_EOL;      

    echo '</div>' . PHP_EOL;    
}


private function doReset() {

	$logs = array(
		"apache2/access.log",
		"apache2/error.log",
		"apache2/other_vhosts_access.log",
		"apache2/ssl_access.log",				
		"asterisk/messages",
		"asterisk/queue_log",
		"auth.log",		
		"fail2ban.log",	
		"mail.err",
		"mail.log",
		"mail.info",
		"mail.warn",
		"messages",
		"mysql.log",
		"srkhelper.log",				
		"shorewall-init.log",				
		"syslog",
		"user.log",
		"wtmp"
	);


//	return;
	$dhcp_reset_string = 
		"auto lo eth0\niface lo inet loopback\n".
		"iface eth0 inet dhcp\n".
		"allow-hotplug wlan0\n". 
		"iface wlan0 inet manual\n". 
		"wpa-roam /etc/wpa_supplicant.conf\n". 
		"iface default inet dhcp\n" .
		"source /etc/network/interfaces.d/*\n";
	$hosts_reset_string = 
		"127.0.0.1 localhost\n" .
		"127.0.1.1 s200" .
		"127.0.0.1 debian";
	
	if ( isset($_POST['resetdb'] ) ) {
		if ($this->live) {
			$this->helper->request_syscmd ("mv /opt/sark/db/sark.db /opt/sark/db/sark.db.insurance");
			$this->helper->request_syscmd ("sh /opt/sark/scripts/srkV4reload");
		}
		$this->log .= "<p>database RESET</p>";
	}
	else {
		$this->log .= "<p>database PRESERVED</p>";	
	}
	if ( isset($_POST['backups'] ) ) {
//		if ($this->live) {
			$this->helper->request_syscmd ("rm -rf /opt/sark/bkup/*");
//		}
		$this->log .= "<p>backups DELETED</p>";
	}
	else {
		$this->log .= "<p>backups PRESERVED</p>";	
	}	
	if ( isset($_POST['snaps'] ) ) {
//		if ($this->live) {
			$this->helper->request_syscmd ("rm -rf /opt/sark/snap/*");
//		}
		$this->log .= "<p>snaps DELETED</p>";
	}
	else {
		$this->log .= "<p>snaps PRESERVED</p>";	
	}		 			
	if ( isset($_POST['usergreets'] ) ) {
//		if ($this->live) {
			$this->helper->request_syscmd ("rm -rf /usr/share/asterisk/sounds/usergreeting*");
//		}
		$this->log .= "<p>greetings DELETED</p>";
	}
	else {
		$this->log .= "<p>greetings PRESERVED</p>";	
	}	
	if ( isset($_POST['vmail'] ) ) {
//		if ($this->live) {
			$this->helper->request_syscmd ("rm -rf /var/spool/asterisk/voicemail/default/*");
//		}
		$this->log .= "<p>voicemail DELETED</p>";
	}
	else {
		$this->log .= "<p>voicemail PRESERVED</p>";	
	}	
	if ( isset($_POST['vrec'] ) ) {
//		if ($this->live) {
			$this->helper->request_syscmd ("rm -rf /var/spool/asterisk/monitor/*");
			$this->helper->request_syscmd ("rm -rf /var/spool/asterisk/monout/*");
			$this->helper->request_syscmd ("rm -rf /var/spool/asterisk/monstage/*");
//		}
		$this->log .= "<p>recordings DELETED</p>";
	}
	else {
		$this->log .= "<p>recordings PRESERVED</p>";	
	}				
	if ( isset($_POST['cdrs'] ) ) {
//		if ($this->live) {
			$this->helper->request_syscmd ("cat /dev/null > /var/log/asterisk/cdr-csv/Master.csv");
//		}
		$this->log .= "<p>CDRs DELETED</p>";
	}
	else {
		$this->log .= "<p>CDRs PRESERVED</p>";	
	}	
	if ( isset($_POST['logs'] ) ) {
		foreach ($logs as $log) {
			$this->helper->request_syscmd ("cat /dev/null > /var/log/$log");
			$this->helper->request_syscmd ('rm -rf /var/log/' . $log . '.*');
		}
		$this->log .= "<p>logs DELETED</p>";
	}
	else {
		$this->log .= "<p>logs PRESERVED</p>";	
	}		
	if ( isset($_POST['firewall'] ) ) {
//		if ($this->live) {
			$this->helper->request_syscmd ("cp -a /opt/sark/cache/sark_rules_reset /etc/shorewall/sark_rules");
			$this->helper->request_syscmd ("sed -i 's|^Ping/REJECT|Ping/ACCEPT|' /etc/shorewall/rules");
			$this->helper->request_syscmd ("shorewall restart");
//		}	
		$this->log .= "<p>firewall RESET</p>";
		$this->log .= "<p>firewall RESTARTED</p>";
	}
	else {
		$this->log .= "<p>firewall PRESERVED</p>";	
	}			
	if ( isset($_POST['dhcp'] ) ) {
//		if ($this->live) {
			$this->helper->request_syscmd ("echo $dhcp_reset_string > /etc/network/interfaces");
			$this->helper->request_syscmd ('echo "nameserver 8.8.8.8" > /etc/resolv.dnsmasq');
			$this->helper->request_syscmd ('echo "nameserver 8.8.8.8" > /etc/resolv.conf');
			$this->helper->request_syscmd ("rm -rf /etc/dnsmasq.d/sarkdhcp-range");
			$this->helper->request_syscmd ('echo "resolv-file=/etc/resolv.dnsmasq" > /etc/dnsmasq.d/sarkdns');
			
			$this->helper->request_syscmd ("cat /dev/null > /etc/dnsmasq.d/sarkdhcp-router");
			$this->helper->request_syscmd ("cat /dev/null > /etc/dnsmasq.d/sarkdhcp-domain");
			$this->helper->request_syscmd ("cat /dev/null > /etc/dnsmasq.d/sarkdhcp-dnssrv");
			
			$this->helper->request_syscmd ("cp -a /opt/sark/cache/ssmtp-reset.conf /etc/ssmtp/ssmtp.conf");
			$this->helper->request_syscmd ("cp -a /opt/sark/cache/revaliases-reset /etc/ssmtp/revaliases");	
			$this->helper->request_syscmd ("cp -a /opt/sark/cache/ntp-reset.conf /etc/ntp.conf");						
			$this->helper->request_syscmd ("cat /dev/null > /etc/resolv.conf");
//		}
		$this->log .= "<p>network RESET</p>";	
	}
	else {
		$this->log .= "<p>network PRESERVED</p>";	
	}		
	if ( isset($_POST['host'] ) ) {
//		if ($this->live) {
			$this->helper->request_syscmd ("echo s200 > /etc/hostname");
//		}
		$this->log .= "<p>hostname RESET</p>";
	}
	else {
		$this->log .= "<p>hostname PRESERVED</p>";	
	}	
	if ( isset($_POST['sshport'] ) ) {
//		if ($this->live) {
			$this->helper->request_syscmd ("/bin/sed -i 's/^Port [0-9][0-9]*/Port 22/' /etc/ssh/sshd_config");
//		}
		$this->log .= "<p>sshport RESET</p>";		
	}
	else {
		$this->log .= "<p>sshport PRESERVED</p>";	
	}
	if ( isset($_POST['ldap'] ) ) {
//		if ($this->live) {
			$this->helper->request_syscmd ("/etc/init.d/slapd stop");
			$this->helper->request_syscmd ("rm -rf /var/lib/ldap/*");
			$this->helper->request_syscmd ("slapadd -l /opt/sark/cache/sark.local.empty.ldif");
			$this->helper->request_syscmd ("chown openldap:openldap /var/lib/ldap/*");
			$this->helper->request_syscmd ("/etc/init.d/slapd start");			
//		}
		$this->log .= "<p>LDAP Directory RESET</p>";
	}
	else {
		$this->log .= "<p>LDAP Directory PRESERVED</p>";	
	}
		
	$this->log .= "<br/><br/><p>Rebooting...</p>";

}
}

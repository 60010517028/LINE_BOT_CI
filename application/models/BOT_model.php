<?php
class BOT_model extends CI_Model{

	const EVENT_TABLE = 'event';
	const MEMBER_TABLE = '_MEMBER';
	const URL_TABLE = 'URL';
    const LOG_TABLE = '_LOG';

	 public function __construct()
	 {
	 	parent::__construct();
	 }

	 public function insert($table,$data){
	 	$this->db->insert($table, $data);
	 }

	public function deleteMember($table,$UID){
		$this->db->delete(self::MEMBER_TABLE, array('uid' => $UID));
	}

	public function getEventList(){
		return $this->db->get(self::EVENT_TABLE)->result();
	}

	public function getEvent($UID){
	 	return $this->db->order_by('date_time', 'DESC')->get_where(self::EVENT_TABLE, array('UID' => $UID))->row();
	}

	public function getMemberList(){
	 	return $this->db->get(self::MEMBER_TABLE)->result();
	}

	public function get_member($UID){
	 	return $this->db->get_where(self::MEMBER_TABLE,array('UID' => $UID))->row();
	}

	#รายการ URL
	public function getURLList($Category){
		return $this->db->get_where(self::URL_TABLE,array('URL_CAT' => $Category))->result();
   }

	public function update($table,$data,$where){
		$this->db->update($table, $data, $where);
    }
    

    public function insert_log($dataLog){
        $this->db->insert(self::LOG_TABLE,$dataLog);
    }


}

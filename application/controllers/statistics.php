<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Statistics extends CI_Controller {
	function __construct() {
		parent::__construct();
	}
	public function index() {
		if(!$this->session->userdata('mbr_id')) {
			redirect(base_url());
		}

		if($this->db->dbdriver == 'mysqli') {
			$substring = 'SUBSTRING';
		} else {
			$substring = 'SUBSTR';
		}

		$data = array();

		$date_ref = date('Y-m-d H:i:s', time() - 3600 * 24 * 30);

		$data['subscriptions_total'] = $this->db->query('SELECT COUNT(DISTINCT(sub.sub_id)) AS ref_value FROM '.$this->db->dbprefix('subscriptions').' AS sub LEFT JOIN '.$this->db->dbprefix('feeds').' AS fed ON fed.fed_id = sub.fed_id WHERE sub.mbr_id = ? AND fed.fed_id IS NOT NULL', array($this->member->mbr_id))->row()->ref_value;

		$data['read_items_30'] = $this->db->query('SELECT COUNT(DISTINCT(hst.itm_id)) AS ref_value FROM '.$this->db->dbprefix('history').' AS hst WHERE hst.hst_real = ? AND hst.hst_datecreated >= ? AND hst.mbr_id = ?', array(1, $date_ref, $this->member->mbr_id))->row()->ref_value;

		$data['date_first_read'] = $this->db->query('SELECT MIN(hst.hst_datecreated) AS ref_value FROM '.$this->db->dbprefix('history').' AS hst WHERE hst.hst_real = ? AND hst.mbr_id = ?', array(1, $this->member->mbr_id))->row()->ref_value;

		if($data['date_first_read']) {
			$data['date_first_read_nice'] = date('F j, Y', strtotime($data['date_first_read']));
		}

		$data['read_items_total'] = $this->db->query('SELECT COUNT(DISTINCT(hst.itm_id)) AS ref_value FROM '.$this->db->dbprefix('history').' AS hst WHERE hst.hst_real = ? AND hst.mbr_id = ?', array(1, $this->member->mbr_id))->row()->ref_value;

		$data['tables'] = '';

		if($this->db->dbdriver == 'mysqli') {
			$legend = array();
			$values = array();
			$query = $this->db->query('SELECT fed.fed_host, IF(sub.sub_title IS NOT NULL, sub.sub_title, fed.fed_title) AS ref, sub.sub_id AS id, IF(sub.sub_direction IS NOT NULL, sub.sub_direction, fed.fed_direction) AS direction, COUNT(DISTINCT(hst.itm_id)) AS nb FROM '.$this->db->dbprefix('history').' AS hst LEFT JOIN '.$this->db->dbprefix('items').' AS itm ON itm.itm_id = hst.itm_id LEFT JOIN '.$this->db->dbprefix('feeds').' AS fed ON fed.fed_id = itm.fed_id LEFT JOIN '.$this->db->dbprefix('subscriptions').' AS sub ON sub.fed_id = fed.fed_id WHERE hst.hst_real = ? AND hst.hst_datecreated >= ? AND hst.mbr_id = ? AND sub.mbr_id = ? GROUP BY id ORDER BY nb DESC LIMIT 0,30', array(1, $date_ref, $this->member->mbr_id, $this->member->mbr_id));
			if($query->num_rows() > 0) {
				foreach($query->result() as $row) {
					if($row->direction) {
						$legend[] = '<a style="background-image:url(https://www.google.com/s2/favicons?domain='.$row->fed_host.'&amp;alt=feed);" class="favicon" dir="'.$row->direction.'" href="'.base_url().'subscriptions/read/'.$row->id.'">'.$row->ref.'</a>';
					} else {
						$legend[] = '<a style="background-image:url(https://www.google.com/s2/favicons?domain='.$row->fed_host.'&amp;alt=feed);" class="favicon" href="'.base_url().'subscriptions/read/'.$row->id.'">'.$row->ref.'</a>';
					}
					$values[] = $row->nb;
				}
			}
			$data['tables'] .= build_table_repartition($this->lang->line('items_read_by_subscription').'*', $values, $legend);

			$legend = array();
			$values = array();
			$query = $this->db->query('SELECT fed.fed_host, IF(sub.sub_title IS NOT NULL, sub.sub_title, fed.fed_title) AS ref, sub.sub_id AS id, IF(sub.sub_direction IS NOT NULL, sub.sub_direction, fed.fed_direction) AS direction, COUNT(DISTINCT(hst.itm_id)) AS nb FROM '.$this->db->dbprefix('history').' AS hst LEFT JOIN '.$this->db->dbprefix('items').' AS itm ON itm.itm_id = hst.itm_id LEFT JOIN '.$this->db->dbprefix('feeds').' AS fed ON fed.fed_id = itm.fed_id LEFT JOIN '.$this->db->dbprefix('subscriptions').' AS sub ON sub.fed_id = fed.fed_id WHERE hst.hst_real = ? AND hst.hst_datecreated >= ? AND hst.mbr_id = ? AND sub.mbr_id = ? GROUP BY id ORDER BY nb ASC LIMIT 0,30', array(1, $date_ref, $this->member->mbr_id, $this->member->mbr_id));
			if($query->num_rows() > 0) {
				foreach($query->result() as $row) {
					if($row->direction) {
						$legend[] = '<a style="background-image:url(https://www.google.com/s2/favicons?domain='.$row->fed_host.'&amp;alt=feed);" class="favicon" dir="'.$row->direction.'" href="'.base_url().'subscriptions/read/'.$row->id.'">'.$row->ref.'</a>';
					} else {
						$legend[] = '<a style="background-image:url(https://www.google.com/s2/favicons?domain='.$row->fed_host.'&amp;alt=feed);" class="favicon" href="'.base_url().'subscriptions/read/'.$row->id.'">'.$row->ref.'</a>';
					}
					$values[] = $row->nb;
				}
			}
			$data['tables'] .= build_table_repartition($this->lang->line('items_read_by_subscription_less').'*', $values, $legend);
		}

		if($this->config->item('tags')) {
			$legend = array();
			$values = array();
			$query = $this->db->query('SELECT cat.cat_title AS ref, COUNT(DISTINCT(hst.itm_id)) AS nb FROM '.$this->db->dbprefix('history').' AS hst LEFT JOIN '.$this->db->dbprefix('items').' AS itm ON itm.itm_id = hst.itm_id LEFT JOIN '.$this->db->dbprefix('subscriptions').' AS sub ON sub.fed_id = itm.fed_id LEFT JOIN '.$this->db->dbprefix('categories').' AS cat ON cat.itm_id = itm.itm_id WHERE cat.cat_id IS NOT NULL AND hst.hst_real = ? AND hst.hst_datecreated >= ? AND hst.mbr_id = ? AND sub.mbr_id = ? GROUP BY ref ORDER BY nb DESC LIMIT 0,30', array(1, $date_ref, $this->member->mbr_id, $this->member->mbr_id));
			if($query->num_rows() > 0) {
				foreach($query->result() as $row) {
					$legend[] = '<i class="icon icon-tag"></i>'.$row->ref;
					$values[] = $row->nb;
				}
			}
			$data['tables'] .= build_table_repartition($this->lang->line('items_read_by_tag').'*', $values, $legend);
		}

		$legend = array();
		$values = array();
		$query = $this->db->query('SELECT '.$substring.'(hst.hst_datecreated, 1, 10) AS ref, COUNT(DISTINCT(hst.itm_id)) AS nb FROM '.$this->db->dbprefix('history').' AS hst WHERE hst.hst_real = ? AND hst.mbr_id = ? GROUP BY ref ORDER BY ref DESC LIMIT 0,30', array($this->session->userdata('timezone'), 1, $this->member->mbr_id));
		if($query->num_rows() > 0) {
			foreach($query->result() as $row) {
				$legend[] = '<i class="icon icon-calendar"></i>'.$this->readerself_library->timezone_datetime($row->ref, 'F j, Y');
				$values[] = $row->nb;
			}
		}
		$data['tables'] .= build_table_progression($this->lang->line('items_read_by_day'), $values, $legend);

		if($this->db->dbdriver == 'mysqli') {
			$legend = array();
			$values = array();
			$temp = array();
			$query = $this->db->query('SELECT DATE_FORMAT(DATE_ADD(hst.hst_datecreated, INTERVAL ? HOUR), \'%H\') AS ref, COUNT(DISTINCT(hst.itm_id)) AS nb FROM '.$this->db->dbprefix('history').' AS hst WHERE hst.hst_real = ? AND hst.hst_datecreated >= ? AND hst.mbr_id = ? GROUP BY ref ORDER BY ref ASC', array($this->session->userdata('timezone'), 1, $date_ref, $this->member->mbr_id));
			if($query->num_rows() > 0) {
				foreach($query->result() as $row) {
					$temp[intval($row->ref)] = $row->nb;
				}
			}
			for($i=0;$i<=23;$i++) {
				$legend[] = '<i class="icon icon-time"></i>'.$i.'h';
				if(isset($temp[$i]) == 1) {
					$values[] = $temp[$i];
				} else {
					$values[] = 0;
				}
			}
			$data['tables'] .= build_table_repartition($this->lang->line('items_read_by_time_day').'*', $values, $legend);

			$days = array(7=>'Sunday', 1=>'Monday', 2=>'Tuesday', 3=>'Wednesday', 4=>'Thursday', 5=>'Friday', 6=>'Saturday');
			$legend = array();
			$values = array();
			$temp = array();
			$query = $this->db->query('SELECT IF(DATE_FORMAT(DATE_ADD(hst.hst_datecreated, INTERVAL ? HOUR), \'%w\') = 0, 7, DATE_FORMAT(DATE_ADD(hst.hst_datecreated, INTERVAL ? HOUR), \'%w\')) AS ref, COUNT(DISTINCT(hst.itm_id)) AS nb FROM '.$this->db->dbprefix('history').' AS hst WHERE hst.hst_real = ? AND hst.hst_datecreated >= ? AND hst.mbr_id = ? GROUP BY ref ORDER BY ref ASC', array($this->session->userdata('timezone'), $this->session->userdata('timezone'), 1, $date_ref, $this->member->mbr_id));
			if($query->num_rows() > 0) {
				foreach($query->result() as $row) {
					$temp[$row->ref] = $row->nb;
				}
			}
			foreach($days as $i => $v) {
					$legend[] = '<i class="icon icon-calendar"></i>'.$v;
				if(isset($temp[$i]) == 1) {
					$values[] = $temp[$i];
				} else {
					$values[] = 0;
				}
			}
			$data['tables'] .= build_table_repartition($this->lang->line('items_read_by_day_week').'*', $values, $legend);
		}

		if($this->config->item('folders')) {
			$legend = array();
			$values = array();
			$query = $this->db->query('SELECT flr.flr_title AS ref, flr.flr_id AS id, flr.flr_direction AS direction, COUNT(DISTINCT(hst.itm_id)) AS nb FROM '.$this->db->dbprefix('history').' AS hst LEFT JOIN '.$this->db->dbprefix('items').' AS itm ON itm.itm_id = hst.itm_id LEFT JOIN '.$this->db->dbprefix('subscriptions').' AS sub ON sub.fed_id = itm.fed_id LEFT JOIN '.$this->db->dbprefix('folders').' AS flr ON flr.flr_id = sub.flr_id WHERE hst.hst_real = ? AND hst.hst_datecreated >= ? AND hst.mbr_id = ? AND sub.mbr_id = ? GROUP BY ref ORDER BY nb DESC LIMIT 0,30', array(1, $date_ref, $this->member->mbr_id, $this->member->mbr_id));
			if($query->num_rows() > 0) {
				foreach($query->result() as $row) {
					if($row->ref) {
						if($row->direction) {
							$legend[] = '<a dir="'.$row->direction.'" href="'.base_url().'folders/read/'.$row->id.'"><i class="icon icon-folder-close"></i>'.$row->ref.'</a>';
						} else {
							$legend[] = '<a href="'.base_url().'folders/read/'.$row->id.'"><i class="icon icon-folder-close"></i>'.$row->ref.'</a>';
						}
					} else {
						$legend[] = '<i class="icon icon-folder-close"></i><em>'.$this->lang->line('no_folder').'</em>';
					}
					$values[] = $row->nb;
				}
			}
			$data['tables'] .= build_table_repartition($this->lang->line('items_read_by_folder').'*', $values, $legend);
		}

		$legend = array();
		$values = array();
		$query = $this->db->query('SELECT '.$substring.'(hst.hst_datecreated, 1, 7) AS ref, COUNT(DISTINCT(hst.itm_id)) AS nb FROM '.$this->db->dbprefix('history').' AS hst WHERE hst.hst_real = ? AND hst.mbr_id = ? GROUP BY ref ORDER BY ref DESC LIMIT 0,12', array($this->session->userdata('timezone'), 1, $this->member->mbr_id));
		if($query->num_rows() > 0) {
			foreach($query->result() as $row) {
				$legend[] = '<i class="icon icon-calendar"></i>'.date('F, Y', strtotime($row->ref));
				$values[] = $row->nb;
			}
		}
		$data['tables'] .= build_table_progression($this->lang->line('items_read_by_month'), $values, $legend);

		$content = $this->load->view('statistics_index', $data, TRUE);
		$this->readerself_library->set_content($content);
	}
}

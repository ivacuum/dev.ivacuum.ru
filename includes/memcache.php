<?php
/**
* @package ivacuum.ru
* @copyright (c) 2012
*/

namespace app;

use app\models\page;

/**
* Memcache
*/
class memcache extends page
{
	private $servers = [0 => 'unix:///var/run/memcached/memcached.lock'];
	
	public function _setup()
	{
		if (!$this->auth->acl_get('a_'))
		{
			trigger_error('PAGE_NOT_FOUND');
		}
		
		$this->set_site_submenu();
	}
	
	/**
	* Host stats
	*/
	public function index()
	{
		$stats = $this->get_memcache_stats();
		$stats_single = $this->get_memcache_stats(false);

		$total_uptime = array_sum($stats['uptime']);

		$this->template->assign([
			'CURR_ITEMS'          => num_format($stats['curr_items']),
			'HITS'                => num_format($stats['get_hits']),
			'HITS_PERCENT'        => sprintf('%.2f', ($stats['get_hits'] / ($stats['get_hits'] + $stats['get_misses'])) * 100),
			'LIMIT_MAXBYTES'      => $stats['limit_maxbytes'],
			'MEMCACHE_SERVER'     => $this->servers[0],
			'MEMORY_FREE'         => $stats['limit_maxbytes'] - $stats['bytes'],
			'MEMORY_FREE_PERCENT' => sprintf('%.2f', (($stats['limit_maxbytes'] - $stats['bytes']) / $stats['limit_maxbytes']) * 100),
			'MEMORY_USED'         => $stats['bytes'],
			'MEMORY_USED_PERCENT' => sprintf('%.2f', ($stats['bytes'] / $stats['limit_maxbytes']) * 100),
			'MISSES'              => num_format($stats['get_misses']),
			'MISSES_PERCENT'      => sprintf('%.2f', ($stats['get_misses'] / ($stats['get_hits'] + $stats['get_misses'])) * 100),
			'PHP_VERSION'         => phpversion(),
			'TOTAL_ITEMS'         => num_format($stats['total_items']),

			'HIT_RATE'  => sprintf('%.2f', $stats['get_hits'] / $total_uptime),
			'MISS_RATE' => sprintf('%.2f', $stats['get_misses'] / $total_uptime),
			'REQ_RATE'  => sprintf('%.2f', $stats['cmd_get'] / $total_uptime),
			'SET_RATE'  => sprintf('%.2f', $stats['cmd_set'] / $total_uptime),
		]);

		foreach ($this->servers as $server)
		{
			$this->template->append('servers', [
				'CACHE_TOTAL' => $stats_single[$server]['STAT']['limit_maxbytes'],
				'CACHE_USED'  => $stats_single[$server]['STAT']['bytes'],
				'SERVER'      => $server,
				'START_TIME'  => $this->user->create_date($stats_single[$server]['STAT']['time'] - $stats_single[$server]['STAT']['uptime']),
				'UPTIME'      => create_time($this->user->ctime - ($stats_single[$server]['STAT']['time'] - $stats_single[$server]['STAT']['uptime']), true),
				'VERSION'     => $stats_single[$server]['STAT']['version'],
			]);
		}
	}
	
	/**
	* Item delete
	*/
	public function delete()
	{
		$key = $this->request->variable('key', '');
		
		if (!$key)
		{
			trigger_error('No key set!');
		}
		
		list($host, $port) = $this->get_server_host_port($this->servers[0]);
		
		$this->send_memcache_command($host, $port, 'delete ' . $key);
		
		$this->request->redirect(ilink($this->get_handler_url('variables')));
	}
	
	/**
	* Item dump
	*/
	public function item()
	{
		$key = $this->request->variable('key', '');
		
		if (!$key)
		{
			trigger_error('No key set!');
		}
		
		list($host, $port) = $this->get_server_host_port($this->servers[0]);

		$row = $this->send_memcache_command($host, $port, 'get ' . $key);
		$row = $row['VALUE'][$key];
		
		/* Попытки разобрать значение */
		if (false === $value = @unserialize($row['value']))
		{
			$value = json_decode($row['value'], true);
			
			if (is_null($value))
			{
				$value = $row['value'];
			}
		}

		$this->template->assign([
			'FLAG'  => $row['stat']['flag'],
			'KEY'   => $key,
			'SIZE'  => $row['stat']['size'],
			'VALUE' => wordwrap(print_r($value, true), 80, "\n", true)
		]);
	}
	
	/**
	* Variables
	*/
	public function variables()
	{
		$cache_items = $this->get_cache_items();
		$vars = [];

		foreach ($cache_items['items'] as $server => $entries)
		{
			foreach ($entries as $slab_id => $slab)
			{
				$items = $this->dump_cache_slab($server, $slab_id, $slab['number']);

				foreach ($items['ITEM'] as $key => $info)
				{
					preg_match('#^\[(\d+) b\; (\d+) s\]$#', $info, $match);

					$vars[$key] = [
						'EXPIRE' => $this->user->ctime > $match[2] ? 'expired' : $this->user->create_date($match[2]),
						'SIZE'   => $match[1],
					];
				}
			}
		}

		ksort($vars);
		
		$this->template->assign('vars', $vars);
	}
	
	private function dump_cache_slab($server, $slab_id, $limit)
	{
		list($host, $port) = $this->get_server_host_port($server);

		return $this->send_memcache_command($host, $port, 'stats cachedump ' . $slab_id . ' ' . $limit);
	}
	
	private function get_cache_items()
	{
		$items        = $this->send_memcache_commands('stats items');
		$server_items = [];
		$total_items  = [];

		foreach ($items as $server => $itemlist)
		{
			$server_items[$server] = [];
			$total_items[$server]  = 0;

			if (!isset($itemlist['STAT']))
			{
				continue;
			}

			$iteminfo = $itemlist['STAT'];

			foreach ($iteminfo as $keyinfo => $value)
			{
				if (preg_match('#items\:(\d+?)\:(.+?)$#', $keyinfo, $matches))
				{
					$server_items[$server][$matches[1]][$matches[2]] = $value;

					if ($matches[2] == 'number')
					{
						$total_items[$server] += $value;
					}
				}
			}
		}

		return ['items' => $server_items, 'counts' => $total_items];
	}
	
	private function get_memcache_stats($total = true)
	{
		$resp = $this->send_memcache_commands('stats');
		
		if (!$total)
		{
			return $resp;
		}

		$res = [];

		foreach ($resp as $server => $r)
		{
			foreach ($r['STAT'] as $key => $row)
			{
				if (!isset($res[$key]))
				{
					$res[$key] = null;
				}

				switch ($key)
				{
					case 'pid': $res['pid'][$server] = $row; break;
					case 'uptime': $res['uptime'][$server] = $row; break;
					case 'time': $res['time'][$server] = $row; break;
					case 'version': $res['version'][$server] = $row; break;
					case 'pointer_size': $res['pointer_size'][$server] = $row; break;
					case 'rusage_user': $res['rusage_user'][$server] = $row; break;
					case 'rusage_system': $res['rusage_system'][$server] = $row; break;
					case 'curr_items': $res['curr_items'] += $row; break;
					case 'total_items': $res['total_items'] += $row; break;
					case 'bytes': $res['bytes'] += $row; break;
					case 'curr_connections': $res['curr_connections'] += $row; break;
					case 'total_connections': $res['total_connections'] += $row; break;
					case 'connection_structures': $res['connection_structures'] += $row; break;
					case 'cmd_get': $res['cmd_get'] += $row; break;
					case 'cmd_set': $res['cmd_set'] += $row; break;
					case 'get_hits': $res['get_hits'] += $row; break;
					case 'get_misses': $res['get_misses'] += $row; break;
					case 'evictions': $res['evictions'] += $row; break;
					case 'bytes_read': $res['bytes_read'] += $row; break;
					case 'bytes_written': $res['bytes_written'] += $row; break;
					case 'limit_maxbytes': $res['limit_maxbytes'] += $row; break;
					case 'threads': $res['rusage_system'][$server] = $row; break;
				}
			}
		}

		return $res;
	}
	
	private function get_server_host_port($server)
	{
		return false !== strpos($server, 'unix://') ? [$server, 0] : explode(':', $server);
	}
	
	private function flush_server($server)
	{
		list($host, $port) = $this->get_server_host_port($server);

		return $this->send_memcache_command($host, $port, 'flush_all');
	}
	
	private function parse_memcache_results($str)
	{
		$res   = [];
		$lines = explode("\r\n", $str);

		for ($i = 0, $len = sizeof($lines); $i < $len; $i++)
		{
			$line = $lines[$i];
			$l = explode(' ', $line, 3);

			if (3 == sizeof($l))
			{
				$res[$l[0]][$l[1]] = $l[2];

				if ($l[0] == 'VALUE')
				{
					/* next line is the value */
					$res[$l[0]][$l[1]] = [];
					list($flag, $size) = explode(' ', $l[2]);
					$res[$l[0]][$l[1]]['stat'] = ['flag' => $flag, 'size' => $size];
					$res[$l[0]][$l[1]]['value'] = $lines[++$i];
				}
			}
			elseif ($line == 'DELETED' || $line == 'NOT_FOUND' || $line == 'OK')
			{
				return $line;
			}
		}

		return $res;
	}
	
	private function send_memcache_command($server, $port, $command)
	{
		$s = @fsockopen($server, $port, $errno, $errstr);

		if (!$s)
		{
			trigger_error('Cant connect to: ' . $server . ':' . $port . '(' . $errno . ') - ' . $errstr);
		}

		fwrite($s, $command . "\r\n");

		$buf = '';

		while (!feof($s))
		{
			$buf .= fgets($s, 256);
			
			if (false !== strpos($buf, "END\r\n"))
			{
				/* stat says end */
				break;
			}
			if (false !== strpos($buf, "DELETED\r\n") || false !== strpos($buf, "NOT_FOUND\r\n"))
			{
				/* delete says these */
				break;
			}
			if (false !== strpos($buf, "OK\r\n"))
			{
				/* flush_all says ok */
				break;
			}
		}

		fclose($s);

		return $this->parse_memcache_results($buf);
	}

	private function send_memcache_commands($command)
	{
		$result = [];

		foreach ($this->servers as $server)
		{
			list($host, $port) = $this->get_server_host_port($server);

			$result[$server] = $this->send_memcache_command($host, $port, $command);
		}

		return $result;
	}
}

<?php
namespace WorkWechatBundle\OvertrueWorkWechat\Extend\Callback;
use WorkWechatBundle\OvertrueWorkWechat\Extend\Callback\ErrorCode;
// include_once "errorCode.php";

/**
 * XMLParse class
 *
 * 提供提取消息格式中的密文及生成回复消息格式的接口.
 */
class XMLParse
{

	/**
	 * 提取出xml数据包中的加密消息
	 * @param string $xmltext 待提取的xml字符串
	 * @return string 提取出的加密消息字符串
	 */
	public function extract($xmltext)
	{
		try {
			$xml = new \DOMDocument();
			$xml->loadXML($xmltext);
			$array_e = $xml->getElementsByTagName('Encrypt');
			$encrypt = $array_e->item(0)->nodeValue;
			return array(0, $encrypt);
		} catch (\Exception $e) {
			print $e . "\n";
			return array(ErrorCode::$ParseXmlError, null);
		}
	}

	/**
	 * 生成xml消息
	 * @param string $encrypt 加密后的消息密文
	 * @param string $signature 安全签名
	 * @param string $timestamp 时间戳
	 * @param string $nonce 随机字符串
	 */
	public function generate($encrypt, $signature, $timestamp, $nonce)
	{
		$format = "<xml>
<Encrypt><![CDATA[%s]]></Encrypt>
<MsgSignature><![CDATA[%s]]></MsgSignature>
<TimeStamp>%s</TimeStamp>
<Nonce><![CDATA[%s]]></Nonce>
</xml>";
		return sprintf($format, $encrypt, $signature, $timestamp, $nonce);
	}

}

//
// Test 
/*
$sPostData = "<xml><ToUserName><![CDATA[toUser]]></ToUserName><AgentID><![CDATA[toAgentID]]></AgentID><Encrypt><![CDATA[msg_encrypt]]></Encrypt></xml>";
$xmlparse = new XMLParse;
$array = $xmlparse->extract($sPostData);
var_dump($array);
*/


?>

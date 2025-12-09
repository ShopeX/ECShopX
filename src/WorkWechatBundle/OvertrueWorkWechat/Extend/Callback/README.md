注意事项：
-------------
- Sample.php提供了示例以供开发者参考。  
- errorCode.php, pkcs7Encoder.php, sha1.php, xmlparse.php文件是实现这个类的辅助类，开发者无须关心其具体实现。  
- WXBizMsgCrypt.php文件提供了WXBizMsgCrypt类的实现，是用户接入企业微信的接口类,   
  包括VerifyURL, DecryptMsg, EncryptMsg三个接口，分别用于开发者验证回调url，收到用户回复消息的解密以及开发者回复消息的加密过程  
  使用方法可以参考Sample.php文件。
- 加解密协议请参考企业微信官方文档。
- 经测试，PHP 5.3.3 ~ 7.2.0 版本均可使用

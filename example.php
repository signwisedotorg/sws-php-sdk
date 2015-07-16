<?php

/**
 * Example usage of SignWise class.
 */

require_once 'class.SignWise.php';

$swConf = array(
  'server' => 'https://dtm-test.signwise.me/',
  'certificate' => 'cert/cert.crt',
  'privateKey' => 'cert/private.key',
  'defaultFileProxyUrl' => 'http://www.example.com/my-files/',
  'defaultContainerType' => 'bdoc',
);
$sw = new SignWise($swConf);

echo "API Version: " . $sw->getVersion()->pkg->version . "<br>";

// Create a new container of a document that exists in your file storage.
$sw->createContainer('my-container.bdoc', array('document.txt'));

// Get container info
print_r($sw->containerInfo('my-container.bdoc'));

// Read a file from container into string
$fileContents = $sw->download('my-container.bdoc', 'document.txt');

// Share the document
$recipients = array(
  "email" => "john@example.com",
  "language" => "et-EE",
  "tmpPath" => "my-container.bdoc.tmp",
);
$sw->createShare('my-container.bdoc', '2015-12-31 23:59:59', $recipients);

// Sign the document with smart card
$testCertificate = "-----BEGIN CERTIFICATE-----\r\nMIIEqDCCA5CgAwIBAgIQXZSW5EBkctNPfCkprF2XsTANBgkqhkiG9w0BAQUFADBs\r\nMQswCQYDVQQGEwJFRTEiMCAGA1UECgwZQVMgU2VydGlmaXRzZWVyaW1pc2tlc2t1\r\nczEfMB0GA1UEAwwWVEVTVCBvZiBFU1RFSUQtU0sgMjAxMTEYMBYGCSqGSIb3DQEJ\r\nARYJcGtpQHNrLmVlMB4XDTEyMDQwNDEwNTc0NFoXDTE1MDQwNDIwNTk1OVowga4x\r\nCzAJBgNVBAYTAkVFMRswGQYDVQQKDBJFU1RFSUQgKE1PQklJTC1JRCkxGjAYBgNV\r\nBAsMEWRpZ2l0YWwgc2lnbmF0dXJlMSgwJgYDVQQDDB9URVNUTlVNQkVSLFNFSVRT\r\nTUVTLDE0MjEyMTI4MDI1MRMwEQYDVQQEDApURVNUTlVNQkVSMREwDwYDVQQqDAhT\r\nRUlUU01FUzEUMBIGA1UEBRMLMTQyMTIxMjgwMjUwgZ8wDQYJKoZIhvcNAQEBBQAD\r\ngY0AMIGJAoGBAMFo0cOULrm6HHJdMsyYVq6bBmCU4rjg8eonNnbWNq9Y0AAiyIQv\r\nJ3xDULnfwJD0C3QI8Y5RHYnZlt4U4Yt4CI6JenMySV1hElOtGYP1EuFPf643V11t\r\n/mUDgY6aZaAuPLNvVYbeVHv0rkunKQ+ORABjhANCvHaErqC24i9kv3mVAgMBAAGj\r\nggGFMIIBgTAJBgNVHRMEAjAAMA4GA1UdDwEB/wQEAwIGQDCBmQYDVR0gBIGRMIGO\r\nMIGLBgorBgEEAc4fAwEBMH0wWAYIKwYBBQUHAgIwTB5KAEEAaQBuAHUAbAB0ACAA\r\ndABlAHMAdABpAG0AaQBzAGUAawBzAC4AIABPAG4AbAB5ACAAZgBvAHIAIAB0AGUA\r\ncwB0AGkAbgBnAC4wIQYIKwYBBQUHAgEWFWh0dHA6Ly93d3cuc2suZWUvY3BzLzAn\r\nBgNVHREEIDAegRxzZWl0c21lcy50ZXN0bnVtYmVyQGVlc3RpLmVlMB0GA1UdDgQW\r\nBBSBiUUnibDAPTHAuhRAwSvWzPfoEjAYBggrBgEFBQcBAwQMMAowCAYGBACORgEB\r\nMB8GA1UdIwQYMBaAFEG2/sWxsbRTE4z6+mLQNG1tIjQKMEUGA1UdHwQ+MDwwOqA4\r\noDaGNGh0dHA6Ly93d3cuc2suZWUvcmVwb3NpdG9yeS9jcmxzL3Rlc3RfZXN0ZWlk\r\nMjAxMS5jcmwwDQYJKoZIhvcNAQEFBQADggEBAKPzonf5auRAC8kX6zQTX0yYeQvv\r\nl2bZdbMmDAp07g3CxEaC6bk8DEx9pOJR2Wtm7J9wQke6+HpLEGgNVTAllm+oE4sU\r\nVsaIqFmrcqilWqeWIpj5uR/yU4GDDD9jAGFZtOLaFgaGCwE5++q/LZhosyyAGgvD\r\nyl+yGm5IxTRQ9uflppNZ7k2LoFkoDJhgqHqMZQjwN1kJQ/VBReCRMGUVj5wkBLTJ\r\no9GcMiugyKQib9I6vV9TdemUXKgL+MYp2S8LeIBt0eUXvpp8n/3HIKJIyJpdVvK1\r\nwX5bWYM2o6dT7FAftrkVnShTsEACuRBYSi/4a4hTsSeQTa2Oz1GoNZ7ADXI=\r\n-----END CERTIFICATE-----\r\n";
$sw->prepareSignature("my-container.bdoc", "my-container.bdoc.tmp", $testCertificate);
$testSignature = "3a8482b44a12600db2b67dd273a14becaeca03fa92e7bf13d85a6f3b03d6dca47c3cb77cd735ebe00bfd8442c5ac90d85c65705294c37531e749092a34560e5be7e54b1e3fc05f75def9a652e18417694f64a0a4735311ae20db856759a0caddd3a22d5b69df9ce2cf8a607d82e561e9e5274511cd63ab4a16ae8e3ac77a7970924d520159c7189ee86a91b8447be94b70c06adabeeb513336df10bef25cb2205dcc6028ff6158e5cea4098c11642c150ca62845567a535ded6e8703a8d79e8fa5d4f65022c57dd00b6e88bacafbf48cef1a9fea3705e646f33f9219af2ab81ce18c7aa3fece2bbe2993b352639210a76afde687d883d5445d036892a904ea37";
$sw->finalizeSignature("my-container.bdoc", $testSignature);

// Authenticate with Mobile ID
$userInfo = array("language" => "et", "ssn" => "51001091072", "msisdn" => "37260000007");
$mobileSession = uniqid('', true);
$sw->mobileSigning("my-container.bdoc", "my-container.bdoc.tmp", "http://www.example.com/mobile-id-callback.php?mobileSession=" . $mobileSession, $userInfo);

// Create a template from .rtf file, then create a document from that template and share it.
$placeholders = array(
  array("placeholder" => "[location]", "label" => "Location"),
  array("placeholder" => "[date]", "label" => "Date"),
  array("placeholder" => "[employee]", "label" => "Employee name"),
);
$template = $sw->createTemplate("my_contract_template.rtf", $placeholders, "My Contract Template");
$fields = array(
  array("placeholder" => "[location]", "value" => "Tallinn, Estonia"),
  array("placeholder" => "[date]", "value" => "2015-12-31"),
  array("placeholder" => "[employee]", "value" => "John Smith"),
);
$document = $sw->createDocument($template->id, "contract.pdf", $fields);
$sw->createDocumentShare($document->id, '2015-12-31 23:59:59', $recipients, "My Share Name");

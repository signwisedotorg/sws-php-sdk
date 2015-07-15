<?php

/**
 * PHP SDK for SignWise Services
 */

class SignWise
{

  private $_server;
  private $_certificate;
  private $_privateKey;
  private $_curl;
  private $_defaultFileProxyUrl;
  private $_defaultContainerType;

  /**
   * @param array $conf Configuration array
   *   "server" - Endpoint for SignWise Services
   *   "certificate" - Local path to your generated certificate file in PEM format
   *   "privateKey" - Local path to your generated private key file
   *   "defaultFileProxyUrl' - optional - Full URL pointing to your file proxy URL with path.
   *       Specifying this URL will let you address files with only their basename later on.
   *   "defaultContainerType" - optional - Default container type you choose to use (e.g "bdoc").
   *       Specifying the default type will save you from providing container type to function calls explicitly. 
   */
  public function __construct(array $conf) {
    $this->_server = $conf["server"];
    if ($this->_server && substr($this->_server, -1) !== '/') {
      $this->_server .= '/';
    }
    $this->_certificate = $conf["certificate"];
    $this->_privateKey = $conf["privateKey"];
    if ($conf["defaultFileProxyUrl"]) {
      $this->_defaultFileProxyUrl = $conf["defaultFileProxyUrl"];
    }
    if ($conf["defaultContainerType"]) {
      $this->_defaultContainerType = $conf["defaultContainerType"];
    }
    $this->_curl = curl_init();
  }

  /**
   * Get Server API version.
   * @return object
   */
  public function getVersion() {
    return $this->_get("version");
  }

  /**
   * Download container or a file from container.
   * 
   * @param string $containerPath You can specify only file name if you have provided defaultFileProxyUrl to constructor. Otherwise you must specify full URL.
   * @param null $fileId Specify fileId if you want to download a single file from container. Omit it for whole container.
   * @param null $containerType You can omit this parameter if you have provided defaultContainerType to constructor.
   * @return object|string
   */
  public function download($containerPath, $fileId = null, $containerType = null) {
    $data = array(
      "inputPath" => $this->_fullUrl($containerPath),
      "containerType" => $this->_containerType($containerType),
    );
    if ($fileId) {
      $data["fileId"] = $fileId;
    }
    return $this->_post("container/download", $data, true);
  }

  /**
   * @param string $containerPath You can specify only file name if you have provided defaultFileProxyUrl to constructor. Otherwise you must specify full URL.
   * @param null $containerType You can omit this parameter if you have provided defaultContainerType to constructor.
   * @return object
   */
  public function containerInfo($containerPath, $containerType = null) {
    $data = array(
      "inputPath" => $this->_fullUrl($containerPath),
      "containerType" => $this->_containerType($containerType),
    );
    return $this->_post("container/info", $data);
  }

  /**
   * Share container or PDF for signing and/or viewing.
   *
   * @param string $containerPath Container to share. You can specify only file name if you have provided defaultFileProxyUrl to constructor. Otherwise you must specify full URL.
   * @param string $expireDate Date when sharing will expire. Possible formats: Unix Timestamp; Unix Timestamp with milliseconds; any format accepted by strtotime
   * @param array $recipients List of recipients. Refer to https://developers.signwise.me/dtm/api/#share-create-post for full list of recipient parameters.
   * @param array $options Refer to https://developers.signwise.me/dtm/api/#share-create-post for full list of options.
   * @param null $containerType You can omit this parameter if you have provided defaultContainerType to constructor.
   * @return object
   */
  public function createShare($containerPath, $expireDate, array $recipients, $options = array(), $containerType = null) {
    if (!$options["name"]) {
      $options["name"] = basename($containerPath);
    }
    $data = array(
      "inputPath" => $this->_fullUrl($containerPath),
      "containerType" => $this->_containerType($containerType),
      "expires" => $this->_date($expireDate),
      "recipients" => $recipients,
    );
    return $this->_post("container/share", array_merge($data, $options));
  }

  /**
   * Share a document created from template.
   *
   * @param string $document ID of document created from template.
   * @param string $expireDate Date when sharing will expire. Possible formats: Unix Timestamp; Unix Timestamp with milliseconds; any format accepted by strtotime
   * @param array $recipients List of recipients. Refer to https://developers.signwise.me/dtm/api/#share-create-post for full list of recipient parameters.
   * @param string $name Name for share
   * @param array $options Refer to https://developers.signwise.me/dtm/api/#share-create-post for full list of options.
   * @return object
   */
  public function createDocumentShare($document, $expireDate, array $recipients, $name, $options = array()) {
    $data = array(
      "document" => $document,
      "name" => $name,
      "containerType" => "pdf",
      "expires" => $this->_date($expireDate),
      "recipients" => $recipients,
    );
    return $this->_post("container/share", array_merge($data, $options));
  }

  /**
   * @param string $shareId ID of share
   * @param string $expireDate Date when sharing will expire. Possible formats: Unix Timestamp; Unix Timestamp with milliseconds; any format accepted by strtotime
   * @param array $recipients List of recipients. Refer to https://developers.signwise.me/dtm/api/#share-update-patch for full list of recipient parameters.
   * @param array $options Refer to https://developers.signwise.me/dtm/api/#share-update-patch for full list of options.
   * @return object
   */
  public function updateShare($shareId, $expireDate = null, $recipients = null, $options = array()) {
    if ($expireDate) {
      $options["expires"] = $this->_date($expireDate);
    }
    if ($recipients) {
      $options["recipients"] = $recipients;
    }
    return $this->_patch("container/share/{$shareId}", $options);
  }

  /**
   * @param string $shareId ID of share
   * @param string $message Optional delete message
   * @return object
   */
  public function deleteShare($shareId, $message = null) {
    $data = array();
    if ($message) {
      $data["message"] = $message;
    }
    return $this->_delete("container/share/{$shareId}", $data);
  }

  /**
   * Create new container from files.
   *
   * @param string $outputPath Container destination. You can specify only file name if you have provided defaultFileProxyUrl to constructor. Otherwise you must specify full URL.
   * @param array $files Files to add to container. You can either specify array of path strings or array of objects:
   *    inputPath - You can specify only file name if you have provided defaultFileProxyUrl to constructor. Otherwise you must specify full URL.
   *    fileName - File name that gets stored in container
   *    fileType - File MIME type
   * @param null $containerType You can omit this parameter if you have provided defaultContainerType to constructor.
   * @param bool $overwrite If set and true removes existing container record and creates a new one.
   * @return object
   */
  public function createContainer($outputPath, $files = array(), $containerType = null, $overwrite = true) {
    $data = array(
      "outputPath" => $this->_fullUrl($outputPath),
      "containerType" => $this->_containerType($containerType),
      "overwrite" => $overwrite,
      "files" => $this->_files($files),
    );
    return $this->_post("container", $data);
  }

  /**
   * Add files to container.
   *
   * @param string $containerPath You can specify only file name if you have provided defaultFileProxyUrl to constructor. Otherwise you must specify full URL.
   * @param array $files Files to add to container. You can either specify array of path strings or array of objects:
   *    inputPath - You can specify only file name if you have provided defaultFileProxyUrl to constructor. Otherwise you must specify full URL.
   *    fileName - File name that gets stored in container
   *    fileType - File MIME type
   * @param null $containerType You can omit this parameter if you have provided defaultContainerType to constructor.
   * @return object
   */
  public function addFiles($containerPath, $files = array(), $containerType = null) {
    $data = array(
      "inputPath" => $this->_fullUrl($containerPath),
      "containerType" => $this->_containerType($containerType),
      "files" => $this->_files($files),
    );
    return $this->_put("container/file", $data);
  }

  /**
   * Extracts the specified file(s) from the container. The file(s) are removed from the container and placed to the outputPath.
   *
   * @param string $containerPath Path of the container you want to extract files from. You can specify only file name if you have provided defaultFileProxyUrl to constructor. Otherwise you must specify full URL.
   * @param array $files List of files to extract from container.
   *     outputPath - Path to extract the file to.
   *     fileId - File container internal file ID.
   * @param null $containerType You can omit this parameter if you have provided defaultContainerType to constructor.
   * @return object
   */
  public function extractFiles($containerPath, $files = array(), $containerType = null) {
    $data = array(
      "inputPath" => $this->_fullUrl($containerPath),
      "containerType" => $this->_containerType($containerType),
      "files" => $this->_files($files, true),
    );
    return $this->_post("container/file", $data);
  }

  /**
   * Prepare container for signing with smart card. After successful preparation, the container will be locked until
   * either 1) it has been signed (finalized), 2) it has been cancelled, or 3) 10 minute timeout has passed.
   *
   * @param string $containerPath Path to container you want to sign. You can specify only file name if you have provided defaultFileProxyUrl to constructor. Otherwise you must specify full URL.
   * @param string $tmpPath Path to your file proxy where we store the temporary file.
   * @param string $certificate Certificate read from smart card.
   * @param array $options Additional signing options. Refer to https://developers.signwise.me/dtm/api/#sign-prepare-post for full list of options.
   * @param string $shareId optional If the signature originates from a share, provide the ID of the share.
   * @param string $recipientId optional If the signature originates from a share, provide the ID of the recipient.
   * @param null $containerType You can omit this parameter if you have provided defaultContainerType to constructor.
   * @internal param string $optional $shareId If the signature
   * @return object
   */
  public function prepareSignature($containerPath, $tmpPath, $certificate, $options = array(), $shareId = null, $recipientId = null, $containerType = null) {
    $data = array(
      "inputPath" => $this->_fullUrl($containerPath),
      "containerType" => $this->_containerType($containerType),
      "tmpPath" => $this->_fullUrl($tmpPath),
    );
    if ($shareId) {
      $data["shareId"] = $shareId;
    }
    if ($recipientId) {
      $data["recipientId"] = $recipientId;
    }
    $data = array_merge($data, $options);
    if (!$data["signerInfo"]) {
      $data["signerInfo"] = array();
    }
    $data["signerInfo"]["certificate"] = $certificate;
    return $this->_post("container/sign/prepare", $data);
  }

  /**
   * Finalize container signing with smart card. The container must be prepared first.
   *
   * @param string $containerPath You can specify only file name if you have provided defaultFileProxyUrl to constructor. Otherwise you must specify full URL.
   * @param string $signatureValue Signature calculated by smart card
   * @param string $shareId optional If the signature originates from a share, provide the ID of the share.
   * @param string $recipientId optional If the signature originates from a share, provide the ID of the recipient.
   * @param null $containerType You can omit this parameter if you have provided defaultContainerType to constructor.
   * @return object
   */
  public function finalizeSignature($containerPath, $signatureValue, $shareId = null, $recipientId = null, $containerType = null) {
    $data = array(
      "inputPath" => $this->_fullUrl($containerPath),
      "containerType" => $this->_containerType($containerType),
      "signatureValue" => $signatureValue,
    );
    if ($shareId) {
      $data["shareId"] = $shareId;
    }
    if ($recipientId) {
      $data["recipientId"] = $recipientId;
    }
    return $this->_post("container/sign/finalize", $data);
  }

  /**
   * Prepare and finalize signing a container with Mobile ID.
   *
   * @param string $containerPath You can specify only file name if you have provided defaultFileProxyUrl to constructor. Otherwise you must specify full URL.
   * @param string $tmpPath Path to your file proxy where we store the temporary file.
   * @param string $callbackUrl URL that will receive the response of Mobile ID signing process. The URL must be accessible by SignWise. See callback.php for example.
   * @param array $userInfo Mobile user details required for signing with Mobile ID. Refer to https://developers.signwise.me/dtm/api/#sign-mobile-post for full list of details.
   * @param array $options Refer to https://developers.signwise.me/dtm/api/#sign-mobile-post for full list of options.
   * @param null $containerType You can omit this parameter if you have provided defaultContainerType to constructor.
   * @return object
   */
  public function mobileSigning($containerPath, $tmpPath, $callbackUrl, $userInfo, $options = array(), $containerType = null) {
    $data = array(
      "inputPath" => $this->_fullUrl($containerPath),
      "tmpPath" => $this->_fullUrl($tmpPath),
      "containerType" => $this->_containerType($containerType),
      "callbackURL" => $callbackUrl,
    );
    $data["userInfo"] = $userInfo;
    if ($options) {
      $data = array_merge($data, $options);
    }
    return $this->_post("container/sign/mobile", $data);
  }

  /**
   * Cancel a prepared container and unlock it.
   *
   * @param string $containerPath You can specify only file name if you have provided defaultFileProxyUrl to constructor. Otherwise you must specify full URL.
   * @param string $shareId optional If the prepared signature originates from a share, provide the ID of the share.
   * @param string $recipientId optional If the prepared signature originates from a share, provide the ID of the recipient.
   * @param null $containerType You can omit this parameter if you have provided defaultContainerType to constructor.
   * @return object
   */
  public function cancelSigning($containerPath, $shareId = null, $recipientId = null, $containerType = null) {
    $data = array(
      "inputPath" => $this->_fullUrl($containerPath),
      "containerType" => $this->_containerType($containerType),
    );
    if ($shareId) {
      $data["shareId"] = $shareId;
    }
    if ($recipientId) {
      $data["recipientId"] = $recipientId;
    }
    return $this->_post("container/sign/cancel", $data);
  }

  /**
   * Decline signing a shared container.
   *
   * @param string $containerPath You can specify only file name if you have provided defaultFileProxyUrl to constructor. Otherwise you must specify full URL.
   * @param string $shareId ID of the share.
   * @param string $recipientId ID of the recipient that wants to decline signing.
   * @param string $message optional Decline message (reason)
   * @return object
   */
  public function declineSigning($containerPath, $shareId, $recipientId, $message = null) {
    $data = array(
      "inputPath" => $this->_fullUrl($containerPath),
      "shareId" => $shareId,
      "recipientId" => $recipientId,
    );
    if (false !== $message) {
      $data["message"] = $message;
    }
    return $this->_post("container/sign/decline", $data);
  }

  /**
   * Fetch API usage logs based on specified filters.
   *
   * @param string $inputPath optional Filter for container input path. You can specify only file name if you have provided defaultFileProxyUrl to constructor. Otherwise you must specify full URL.
   * @param string $shareId optional Filter for share ID.
   * @param string $startTime optional Filter for log entry timestamp
   * @param string $endTime optional Filter for log entry timestamp
   * @param number $pageNo optional Each request will return specific number of rows (default 500, configurable for brand via support). If there are more rows to return, next batch is accessible with pageNo=2…..N.
   * @return object
   */
  public function getLogs($inputPath = null, $shareId = null, $startTime = null, $endTime = null, $pageNo = null) {
    $data = array();
    if ($inputPath) {
      $data['inputPath'] = $this->_fullUrl($inputPath);
    }
    if ($shareId) {
      $data['shareId'] = $shareId;
    }
    if ($startTime) {
      $data['startTime'] = $this->_date($startTime);
    }
    if ($endTime) {
      $data['endTime'] = $this->_date($endTime);
    }
    if ($pageNo) {
      $data['pageNo'] = $pageNo;
    }
    return $this->_post("user/log", $data);
  }

  /**
   * Verifies user certificates validity by trying to decrypt user signed random digest with its public certificate and validates OCSP status.
   * Returns boolean true on success.
   *
   * @param string $challenge Your randomly generated challenge.
   * @param string $signature Signature calculated by smart card.
   * @param string $certificate Certificate read from smart card.
   * @return object|bool
   */
  public function authentication($challenge, $signature, $certificate) {
    $data = array(
      "digest" => $challenge,
      "signature" => $signature,
      "certificate" => $certificate,
    );
    return $this->_post("authentication/verify", $data);
  }

  /**
   * Authenticate user with Mobile ID.
   *
   * @param string $callbackUrl URL that will receive the response of Mobile ID authentication process. The URL must be accessible by SignWise. See callback.php for example.
   * @param array $userInfo Mobile user details required for authenticating with Mobile ID. Refer to https://developers.signwise.me/dtm/api/#user-authentication-mobile-verification-post for full list of details.
   * @return object
   */
  public function mobileAuthentication($callbackUrl, $userInfo) {
    $data = array(
      "callbackURL" => $callbackUrl,
      "userInfo" => $userInfo,
    );
    return $this->_post("authentication/mobile", $data);
  }

  /**
   * Parses input certificate.
   *
   * @param string $certificate Certificate (PEM format) to parse
   * @return object
   */
  public function parseCertificate($certificate) {
    $data = array(
      "certificate" => $certificate,
    );
    return $this->_post("certificate/parse", $data);
  }

  /**
   * Validates certificate authenticity via OCSP service. Returns OCSP status. Possible status codes: good, revoked, unknown
   *
   * @param string $certificate Certificate (PEM format) to validate.
   * @return object
   */
  public function validateCertificate($certificate) {
    $data = array(
      "certificate" => $certificate,
    );
    return $this->_post("certificate/ocsp", $data);
  }

  /**
   * Returns Mobile ID certificates.
   *
   * @param array $userInfo Mobile user details required for retrieving Mobile ID certificates. Refer to https://developers.signwise.me/dtm/api/#certificate-mobile-certificates-post for full list of details.
   * @param null $containerType You can omit this parameter if you have provided defaultContainerType to constructor.
   * @return object
   */
  public function mobileCertificates($userInfo, $containerType = null) {
    $data = array(
      "userInfo" => $userInfo,
      "containerType" => $this->_containerType($containerType),
    );
    return $this->_post("certificate/mobile", $data);
  }

  /**
   * Creates a template and returns the object of the template
   *
   * @param string $templatePath Path to document you want to base your template on. You can specify only file name if you have provided defaultFileProxyUrl to constructor. Otherwise you must specify full URL.
   * @param array $placeholders Information about placeholders in your template. Refer to https://developers.signwise.me/dtm/api/#template-create-post for full details on placeholders.
   * @param string $name Name of the template. If omitted, the basename of templatePath is used.
   * @return object
   */
  public function createTemplate($templatePath, $placeholders = array(), $name = null) {
    $data = array(
      "inputPath" => $this->_fullUrl($templatePath),
      "name" => $name ? $name : basename($templatePath),
      "placeholders" => $placeholders,
    );
    return $this->_post("template", $data);
  }

  /**
   * Updates the template and returns the object of the template entity.
   *
   * @param string $templateId ID of the template to update
   * @param array $placeholders optional Information about placeholders in your template to update. Refer to https://developers.signwise.me/dtm/api/#template-update-patch for full details on placeholders.
   * @param string $name optional New name for your template
   * @return object
   */
  public function updateTemplate($templateId, $placeholders = array(), $name = null) {
    $data = array();
    if ($placeholders) {
      $data["placeholders"] = $placeholders;
    }
    if ($name) {
      $data["name"] = $name;
    }
    return $this->_patch("template/{$templateId}", $data);
  }

  /**
   * Deletes the template and returns the object of the template
   *
   * @param string $templateId ID of the template to delete.
   * @return object
   */
  public function deleteTemplate($templateId) {
    return $this->_delete("template/{$templateId}");
  }

  /**
   * Creates a document entity based on template that is given as input and returns the object of the prepared document. PDF is generated when document entity is shared via share method.
   *
   * @param string $templateId ID of the template to base the document on.
   * @param string $outputPath Output path where the document will be saved after sharing it. You can specify only file name if you have provided defaultFileProxyUrl to constructor. Otherwise you must specify full URL.
   * @param array $fields List of placeholders in template to be replaced with text as described in object. Refer to https://developers.signwise.me/dtm/api/#document-create-post for full info on fields.
   * @param string $name optional Name of the document. If omitted, the basename of outputPath is used.
   * @return object
   */
  public function createDocument($templateId, $outputPath, $fields, $name = null) {
    $data = array(
      "template" => $templateId,
      "outputPath" => $this->_fullUrl($outputPath),
      "name" => $name ? $name : basename($outputPath),
      "fields" => $fields,
    );
    return $this->_post("document", $data);
  }

  /**
   * Updates the document and returns the object of the updated document
   *
   * @param string $documentId ID of the document to update.
   * @param array $fields optional List of placeholders in template to be replaced with text as described in object. Refer to https://developers.signwise.me/dtm/api/#document-update-patch for full info on fields.
   * @param string $name optional New name for the document.
   * @return object
   */
  public function updateDocument($documentId, $fields = null, $name = null) {
    $data = array(
      "documentId" => $documentId,
    );
    if ($name) {
      $data["name"] = $name;
    }
    if ($fields) {
      $data["fields"] = $fields;
    }
    return $this->_patch("document/{$documentId}", $data);
  }

  /**
   * Deletes the document and returns the object of the deleted document
   *
   * @param string $documentId ID of the document to delete.
   * @return object
   */
  public function deleteDocument($documentId) {
    return $this->_delete("document/{$documentId}");
  }

  // Input formatting

  private function _fullUrl($path) {
    if (!preg_match("~^[a-z]*://~i", $path)) {
      return $this->_defaultFileProxyUrl . $path;
    } else {
      return $path;
    }
  }

  private function _containerType($containerType) {
    return $containerType ? $containerType : $this->_defaultContainerType;
  }

  private function _date($dateString) {
    if (preg_match("/^[0-9]{10}$/", $dateString)) {
      $dateString = $dateString . '000';
    } elseif (!preg_match("/^[0-9]{13}$/", $dateString)) {
      $dateString = strtotime($dateString) . "000";
    }
    return intval($dateString);
  }

  private function _files($files, $isExtract = false) {
    if (!$files || !is_array($files) || !count($files)) {
      return $files;
    }
    for ($i = 0; $i < count($files); $i++) {
      if (!$isExtract && is_string($files[$i])) {
        $files[$i] = array('inputPath' => $files[$i]);
      }
      $pathField = $isExtract ? "outputPath" : "inputPath";
      $files[$i][$pathField] = $this->_fullUrl($files[$i][$pathField]);
      if (!$isExtract) {
        if (!$files[$i]["fileName"]) {
          $files[$i]["fileName"] = basename($files[$i][$pathField]);
        }
        if (!$files[$i]["fileType"]) {
          $files[$i]["fileType"] = "application/octet-stream";
        }
      }
    }
    return $files;
  }


  // Requests

  private function _get($path) {
    return $this->_request("GET", $path);
  }

  private function _post($path, $body, $raw = false) {
    return $this->_request("POST", $path, $body, $raw);
  }

  private function _put($path, $body) {
    return $this->_request("PUT", $path, $body);
  }

  private function _delete($path, $body = null) {
    return $this->_request("DELETE", $path, $body);
  }

  private function _patch($path, $body) {
    return $this->_request("PATCH", $path, $body);
  }

  private function _request($method, $path, $body = null, $raw = false) {
    $curlOptions = array(
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_VERBOSE => true,
      CURLOPT_HEADER => true,
      CURLOPT_URL => $this->_server . $path
    );
    if (!empty($this->_privateKey)) {
      $curlOptions[CURLOPT_SSLCERT] = $this->_certificate;
      $curlOptions[CURLOPT_SSLKEY] = $this->_privateKey;
    }
    $headers = array();
    if (in_array($method, array("POST", "PUT", "PATCH", "DELETE"))) {
      //$curlOptions[CURLOPT_POST] = true;
      $curlOptions[CURLOPT_CUSTOMREQUEST] = $method;
      if ($body) {
        if (is_array($body)) {
          $body = json_encode($body);
        }
        $curlOptions[CURLOPT_POSTFIELDS] = $body;
        array_push($headers, "Content-Type: application/json");
        array_push($headers, "Content-Length: " . strlen($body));
      }
    }
    if (empty($this->_privateKey)) {
      array_push($headers,  "x-ssl-client-cert: " . str_replace("\n", "", file_get_contents($this->_certificate)));
    }
    if (!empty($headers)) {
      $curlOptions[CURLOPT_HTTPHEADER] = $headers;
    }
    curl_setopt_array($this->_curl, $curlOptions);
    $output = curl_exec($this->_curl);
    if (false === $output) {
      return "Curl Error : " . curl_error($this->_curl);
    } else {
      $headerSize = curl_getinfo($this->_curl, CURLINFO_HEADER_SIZE);
      $body = substr($output, $headerSize);
      if (!trim($body)) {
        $httpCode = curl_getinfo($this->_curl, CURLINFO_HTTP_CODE);
        return $httpCode === 200;
      }
      if ($raw) {
        return $body;
      }
      $result = json_decode($body);
      return $result ? $result : $body;
    }
  }

}
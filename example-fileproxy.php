<?php

/*
  Copyright 2015 SignWise Corporation Ltd.

  Licensed under the Apache License, Version 2.0 (the "License");
  you may not use this file except in compliance with the License.
  You may obtain a copy of the License at

  http://www.apache.org/licenses/LICENSE-2.0

  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License.
*/

/**
 * This is a file proxy example. Make sure SignWise can access it.
 * Also, your file proxy address has to be whitelisted (contact support).
 */

// Root for your file storage
$filesRoot = 'test-files/';

$path = $_SERVER['QUERY_STRING'];
if (empty($path) || (false !== strpos($path, '..'))) {
  header("HTTP/1.0 403 Forbidden");
} elseif ('PUT' === $_SERVER['REQUEST_METHOD']) {
  $body = file_get_contents('php://input');
  $fileName = basename($path);
  $path = substr($path, 0, strlen($path) - strlen($fileName));
  if ($path) {
    mkdir($filesRoot . $path, 0777, true);
  }
  file_put_contents($filesRoot . $path . $fileName, $body);
  header("HTTP/1.0 201 Created");
} elseif ('GET' === $_SERVER['REQUEST_METHOD']) {
  $fullPath = $filesRoot . $path;
  if (is_file($fullPath) && file_exists($fullPath)) {
    readfile($fullPath);
  } else {
    header("HTTP/1.0 404 Not Found");
  }
} else {
  header("HTTP/1.0 405 Method Not Allowed");
}
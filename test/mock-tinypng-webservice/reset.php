<?php

define('SESSION_FILE', '/tmp/session.dat');

if (file_exists(SESSION_FILE)) {
    unlink(SESSION_FILE);
}
<?php
foreach (glob('c:/xampp/htdocs/hr1-crane/Main/uploads/resumes/*') as $f) {
    echo basename($f) . "\n";
}
?>
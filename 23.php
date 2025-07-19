<?php
// Test file cho goto pattern - 23.php
// Đây là file test malware chứa goto statement

// Test goto patterns
goto start;
echo "This should not execute";

start:
echo "Hello from goto!";

// Test với space
goto end;
echo "Between code";

end:
echo "End of script";

// Test goto trong condition
if (true) {
    goto label1;
}

label1:
echo "Label 1 reached";

// Test goto với semicolon
goto finish;

finish:
echo "Script finished";
?> 
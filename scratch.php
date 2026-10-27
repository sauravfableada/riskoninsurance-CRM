<?php

$file = 'resources/views/investment/add_investment.blade.php';
$content = file_get_contents($file);

$pattern = '/<div class="input-group">\s*(<select.*?>.*?<\/select>)\s*<button class="btn btn-outline-primary mb-0" type="button" onclick="(showModal\(\'[^\']+\'\))"><i class="fa-solid fa-plus"><\/i><\/button>\s*<\/div>/is';
$replacement = '<div class="d-flex gap-2 align-items-start">
                                <div class="flex-grow-1" style="min-width: 0;">
                                    $1
                                </div>
                                <button class="btn btn-primary text-white flex-shrink-0 d-flex align-items-center justify-content-center" type="button" onclick="$2" style="width: 38px; height: 38px; border-radius: 0.375rem; padding: 0;"><i class="fa-solid fa-plus"></i></button>
                            </div>';

$content = preg_replace($pattern, $replacement, $content);

$content = str_replace("alert('Please fill in all required fields.');", "Swal.fire({icon: 'error', title: 'Validation Error', text: 'Please fill in all required fields.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000});", $content);

$content = str_replace("alert('Please select family group first and enter name.');", "Swal.fire({icon: 'warning', title: 'Missing Information', text: 'Please select family group first and enter name.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000});", $content);

// In case it has double quotes instead
$content = str_replace('alert("Please fill in all required fields.");', "Swal.fire({icon: 'error', title: 'Validation Error', text: 'Please fill in all required fields.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000});", $content);

file_put_contents($file, $content);
echo "File updated successfully.\n";

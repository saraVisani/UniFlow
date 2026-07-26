<?php

function canEdit($userId, $ownerId) {
    return $userId == $ownerId;
}

function canDelete($userId, $ownerId, $grade) {
    return $userId == $ownerId || $grade >= 2;
}



?>
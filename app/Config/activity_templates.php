<?php

declare(strict_types=1);

// All user-facing activity strings. Indonesian. Keys = activity_type values.
// ActivityLogger builds action strings from these — NEVER ad-hoc strings.

return [
    'card_create'       => '{user} membuat card "{card}"',
    'card_edit'         => '{user} mengubah card "{old}" menjadi "{new}"',
    'card_delete'       => '{user} menghapus card "{card}"',
    'todo_create'       => '{user} menambahkan todo "{todo}" ke card "{card}"',
    'todo_edit'         => '{user} mengubah todo dari "{old}" menjadi "{new}"',
    'todo_delete'       => '{user} menghapus todo "{todo}" dari card "{card}"',
    'todo_status'       => '{user} mengubah status todo "{todo}" dari {old} ke {new}',
    'member_join'       => '{user} bergabung ke workspace',
    'member_approve'    => '{actor} menyetujui permohonan {user}',
    'member_reject'     => '{actor} menolak permohonan {user}',
    'member_kick'       => '{actor} mengeluarkan {user} dari workspace',
    'role_change'       => '{actor} mengubah role {user} dari {old} ke {new}',
    'access_grant'      => '{actor} memberikan akses card "{card}" ke {user}',
    'access_revoke'     => '{actor} mencabut akses card "{card}" dari {user}',
    'workspace_rename'  => '{actor} mengubah nama workspace dari "{old}" ke "{new}"',
    'log_clear'         => '{actor} menghapus seluruh log aktivitas',
    'invite_regenerate' => '{actor} memperbarui kode undangan workspace',
];

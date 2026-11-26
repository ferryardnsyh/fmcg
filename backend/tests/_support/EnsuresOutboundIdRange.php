<?php

trait EnsuresOutboundIdRange
{
    private const MINIMUM_OUTBOUND_ID = 36;

    protected function ensureOutboundIdRange($db): void
    {
        $status = $db->query("SHOW TABLE STATUS LIKE 'outbound'")->getRowArray();
        $nextId = (int) ($status['Auto_increment'] ?? 0);

        if ($nextId < self::MINIMUM_OUTBOUND_ID) {
            $db->query('ALTER TABLE outbound AUTO_INCREMENT = ' . self::MINIMUM_OUTBOUND_ID);
        }
    }
}

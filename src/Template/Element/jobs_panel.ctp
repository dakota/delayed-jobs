<?php
/**
 * @var \App\View\AppView $this
 */
if (empty($jobs)) {
    echo "<p>" . __d('debug_kit', 'No jobs were queued during this request') . "</p>";

    return;
}
?>
<table class="debug-table">
    <tr>
        <th><?= __d('debug_kit', 'ID') ?></th>
        <th class="left"><?= __d('debug_kit', 'Worker') ?></th>
        <th class="left"><?= __d('debug_kit', 'Sequence') ?></th>
        <th class="left"><?= __d('debug_kit', 'Priority') ?></th>
        <th class="left"><?= __d('debug_kit', 'Is queued?') ?></th>
        <th class="left" style="width: 40%;"><?= __d('debug_kit', 'Payload') ?></th>
    </tr>
    <?php foreach ($jobs as $k => $job) : ?>
        <tr>
            <td><?= $job['id'] ?></td>
            <td class="left"><?= $job['worker'] ?></td>
            <td class="left"><?= $job['sequence'] ?: 'None' ?></td>
            <td class="left"><?= $job['priority'] ?></td>
            <td class="left"><?= $job['pushedToBroker'] ? '&#x2714;' : '&#x2715;' ?></td>
            <td class="left">
                <pre><code><?= json_encode($job['payload'], JSON_PRETTY_PRINT) ?></code></pre>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

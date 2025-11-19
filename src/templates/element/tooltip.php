<?php
/**
 * Tooltip Element
 *
 * Display an info icon with tooltip on hover
 *
 * @var \App\View\AppView $this
 * @var string $text Tooltip text to display
 * @var string $position Tooltip position (top, bottom, left, right)
 */

$text = $text ?? '';
$position = $position ?? 'top';
?>

<span class="tooltip-wrapper" data-tooltip="<?= h($text) ?>" data-position="<?= h($position) ?>">
    <span class="tooltip-icon">ℹ️</span>
</span>

<style>
.tooltip-wrapper {
    position: relative;
    display: inline-block;
    margin-left: 6px;
    cursor: help;
}

.tooltip-icon {
    font-size: 14px;
    opacity: 0.7;
    transition: opacity 0.2s ease;
}

.tooltip-wrapper:hover .tooltip-icon {
    opacity: 1;
}

.tooltip-wrapper::before {
    content: attr(data-tooltip);
    position: absolute;
    background: rgba(0, 0, 0, 0.9);
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 13px;
    line-height: 1.4;
    white-space: normal;
    width: max-content;
    max-width: 280px;
    z-index: 1000;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s ease, transform 0.2s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.tooltip-wrapper::after {
    content: '';
    position: absolute;
    width: 0;
    height: 0;
    border: 6px solid transparent;
    z-index: 1001;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s ease, transform 0.2s ease;
}

/* Position: Top (default) */
.tooltip-wrapper[data-position="top"]::before {
    bottom: calc(100% + 10px);
    left: 50%;
    transform: translateX(-50%) translateY(4px);
}

.tooltip-wrapper[data-position="top"]::after {
    bottom: calc(100% + 4px);
    left: 50%;
    transform: translateX(-50%);
    border-top-color: rgba(0, 0, 0, 0.9);
}

/* Position: Bottom */
.tooltip-wrapper[data-position="bottom"]::before {
    top: calc(100% + 10px);
    left: 50%;
    transform: translateX(-50%) translateY(-4px);
}

.tooltip-wrapper[data-position="bottom"]::after {
    top: calc(100% + 4px);
    left: 50%;
    transform: translateX(-50%);
    border-bottom-color: rgba(0, 0, 0, 0.9);
}

/* Position: Left */
.tooltip-wrapper[data-position="left"]::before {
    right: calc(100% + 10px);
    top: 50%;
    transform: translateY(-50%) translateX(4px);
}

.tooltip-wrapper[data-position="left"]::after {
    right: calc(100% + 4px);
    top: 50%;
    transform: translateY(-50%);
    border-left-color: rgba(0, 0, 0, 0.9);
}

/* Position: Right */
.tooltip-wrapper[data-position="right"]::before {
    left: calc(100% + 10px);
    top: 50%;
    transform: translateY(-50%) translateX(-4px);
}

.tooltip-wrapper[data-position="right"]::after {
    left: calc(100% + 4px);
    top: 50%;
    transform: translateY(-50%);
    border-right-color: rgba(0, 0, 0, 0.9);
}

/* Show tooltip on hover */
.tooltip-wrapper:hover::before,
.tooltip-wrapper:hover::after {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}

.tooltip-wrapper[data-position="bottom"]:hover::before {
    transform: translateX(-50%) translateY(0);
}

.tooltip-wrapper[data-position="left"]:hover::before {
    transform: translateY(-50%) translateX(0);
}

.tooltip-wrapper[data-position="right"]:hover::before {
    transform: translateY(-50%) translateX(0);
}

/* Mobile adjustments */
@media (max-width: 768px) {
    .tooltip-wrapper::before {
        max-width: 220px;
        font-size: 12px;
    }
}
</style>

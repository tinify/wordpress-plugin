<?php
function render_percentage_chart($percentage) {
    $width = 200; // same as width of ' .savings-chart > .textual'
    $radius = 80;

    $height = $width;
    $offset = $width / 2;
    $stroke_width = $radius / 2;
    $inner_radius = $radius - ($stroke_width / 2.5);
    $outer_radius = $radius + ($stroke_width * 0.66);
    $full_circle = 2 * pi() * $radius;
    $dash_array_1 = $percentage * $full_circle / 100;

    echo "
        <style>
            .savings-chart svg circle.main {
                fill: #ebebeb;
                stroke: #7acb44;
                stroke-width: $stroke_width;
                stroke-dasharray: $dash_array_1 $full_circle;
                animation: shwoosh 1s ease;
                transition: all 1s ease;
            }
            @keyframes shwoosh {
                from {
                    stroke-dasharray: 0 $full_circle;
                }
                to {
                    stroke-dasharray: $dash_array_1 $full_circle;
                }
            }
            .savings-chart svg circle.inner {
                fill: #fff;
            }
            .savings-chart svg circle.outer {
                fill: #fff;
                fill: none;
                stroke-width: $stroke_width;
                stroke: #fff;
            }
        </style>

    <div class='savings-chart' data-full-circle-size='$full_circle' data-percentage-factor='$radius'>
        <svg width='$width' height='$height'>
          <circle class='main' transform='rotate(-90, $offset, $offset)' r='$radius' cx='$offset' cy='$offset'/>
          <circle class='inner' r='$inner_radius' cx='$offset' cy='$offset' />
          <circle class='outer' r='$outer_radius' cx='$offset' cy='$offset' />
        </svg>
        <div class='textual'>
            <div class='percentage' id='savings-percentage'>$percentage%</div>
            <div class='savings'>savings</div>
        </div>
    </div>";
}
?>


<?php
use \system\classes\Core as Core;
use \system\classes\BlockRenderer as BlockRenderer;

class DiagnosticMsgs_DiagnosticArray_CoreUsage extends BlockRenderer{

    static protected $ICON = [
        "class" => "glyphicon",
        "name" => "dashboard"
    ];

    static protected $ARGUMENTS = [
        "topic" => [
            "name" => "ROS Topic",
            "type" => "text",
            "mandatory" => True
        ],
        "fps" => [
            "name" => "Update frequency (Hz)",
            "type" => "numeric",
            "mandatory" => True,
            "default" => 5
        ],
        "unit" => [
            "name" => "Unit",
            "type" => "text",
            "mandatory" => True
        ],
        "field" => [
            "name" => "Message value to show",
            "type" => "text",
            "mandatory" => True
        ]
    ];

    protected static function render( $id, &$args ){
        ?>
        <canvas class="resizable" style="width:100%; padding:6px; padding-bottom:30px"></canvas>

        <table style="width:100%; height:10px; position:relative; top:-30px">
            <tr>
                <td style="width:35%" class="text-center">
                    0.0
                </td>
                <td style="width:30%" class="text-center">
                    <span style="position:relative; top:-20px">
                        <?php echo $args['unit'] ?>
                    </span>
                </td>
                <td style="width:35%" class="text-center">
                    100.0
                </td>
            </tr>
        </table>

        <script type="text/javascript">

            $( document ).on( "ROSBridge_connected", function(evt){
                // Subscribe to the given topic
                subscriber = new ROSLIB.Topic({
                    ros : window.ros,
                    name : '<?php echo $args['topic'] ?>',
                    messageType : 'diagnostic_msgs/DiagnosticArray',
                    queue_size : 1,
                    throttle_rate : <?php echo intval(1000/$args['fps']) ?>
                });

                chart_config = {
    				type: 'pie',
    				data: {
    					datasets: [{
    						data: [ 0.0, 1.0 ],
                            backgroundColor: [
                                window.chartColors.green,
                                window.chartColors.white
                            ]
    					}]
    				},
    				options: {
    					cutoutPercentage: 50,
                        rotation: -Math.PI,
                        circumference: Math.PI,
                        tooltips: {
                            enabled: false
                        },
                        maintainAspectRatio: false
    				}
    			};
    			// create chart obj
                ctx = $("#<?php echo $id ?> .block_renderer_container canvas")[0].getContext('2d');
    			chart = new Chart(ctx, chart_config);
                window.mission_control_page_blocks_data['<?php echo $id ?>'] = {
                    chart: chart,
                    config: chart_config
                };

                subscriber.subscribe(function(message) {
                    // get chart
                    chart_desc = window.mission_control_page_blocks_data['<?php echo $id ?>'];
                    chart = chart_desc.chart;
                    config = chart_desc.config;
                    // read message
                    for( var idx in message['status'] ){
                        var status = message['status'][idx];
                        if( status['name'].startsWith("CPU Usage") ){
                            for( var idx2 in status.values ){
                                var keyval = status.values[idx2];
                                if( keyval.key == "<?php echo $args['field'] ?>" ){
                                    cur_idle = parseFloat(keyval.value) / 100.0;
                                    // update values
                                    config.data.datasets[0].data[0] = 1.0 - cur_idle;
                                    config.data.datasets[0].data[1] = cur_idle;
                                    // refresh chart
                                    chart.update();
                                }
                            }
                        }
                    }
                });
            });

        </script>

        <?php
    }
}//DiagnosticMsgs_DiagnosticArray_CoreUsage
?>

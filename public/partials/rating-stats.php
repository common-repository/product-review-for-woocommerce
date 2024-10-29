<div class="pisol-review-stats-container">
    <?php foreach($each_rating_count as $rating => $rating_count): ?>
        <div class="pisol-rating-percentage-container">
            <strong class="rating-for"><?php echo $rating; ?> &#9733;</strong>
            <div class="pisol-rating-percentage-bar">
                <div class="pisol-rating-percentage-bar-fill" style="width: <?php echo ($rating_count / $count * 100); ?>%;"></div>
            </div>
            <div class="pisol-rating-percentage">
                <?php echo round(($rating_count / $count * 100),0); ?>%
            </div>
        </div>
    <?php endforeach; ?>
</div>
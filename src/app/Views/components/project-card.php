<?php foreach ($projects as $project): ?>
<section class="panel project-panel" id="project-<?php echo htmlspecialchars($project['slug']); ?>">
    <div class="project-heading">
        <p class="project-label"><?php echo htmlspecialchars($project['label']); ?></p>
        <h2><?php echo htmlspecialchars($project['title']); ?></h2>
        <p class="project-date"><?php echo htmlspecialchars($project['date']); ?></p>
    </div>
    <p class="project-summary"><?php echo htmlspecialchars($project['summary']); ?></p>
    <div class="project-sections">
        <?php foreach ($project['sections'] as $section): ?>
            <article>
                <h3><?php echo htmlspecialchars($section['title']); ?></h3>
                <ul>
                    <?php foreach ($section['items'] as $item): ?>
                        <li><?php echo htmlspecialchars($item); ?></li>
                    <?php endforeach; ?>
                </ul>
            </article>
        <?php endforeach; ?>
    </div>
    <div class="project-impact">
        <h3>Impact</h3>
        <ul>
            <?php foreach ($project['impact'] as $impact): ?>
                <li><?php echo htmlspecialchars($impact); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
<?php endforeach; ?>

<?php

/**
 * Extend PhpStorms code completion capabilities by providing a meta file
 *
 * @link https://www.jetbrains.com/help/phpstorm/ide-advanced-metadata.html
 */

namespace PHPSTORM_META {
    override(\Redmine\Client\Client::getApi(), map([
        'attachment' => \Redmine\Api\Attachment::class,
        'group' => \Redmine\Api\Group::class,
        'custom_fields' => \Redmine\Api\CustomField::class,
        'issue' => \Redmine\Api\Issue::class,
        'issue_category' => \Redmine\Api\IssueCategory::class,
        'issue_priority' => \Redmine\Api\IssuePriority::class,
        'issue_relation' => \Redmine\Api\IssueRelation::class,
        'issue_status' => \Redmine\Api\IssueStatus::class,
        'membership' => \Redmine\Api\Membership::class,
        'news' => \Redmine\Api\News::class,
        'project' => \Redmine\Api\Project::class,
        'query' => \Redmine\Api\Query::class,
        'role' => \Redmine\Api\Role::class,
        'time_entry' => \Redmine\Api\TimeEntry::class,
        'time_entry_activity' => \Redmine\Api\TimeEntryActivity::class,
        'tracker' => \Redmine\Api\Tracker::class,
        'user' => \Redmine\Api\User::class,
        'version' => \Redmine\Api\Version::class,
        'wiki' => \Redmine\Api\Wiki::class,
        'search' => \Redmine\Api\Search::class,
    ]));
}

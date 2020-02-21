package docs;

import com.atlassian.bamboo.specs.api.builders.credentials.SharedCredentialsIdentifier;
import com.atlassian.bamboo.specs.api.builders.permission.PermissionType;
import com.atlassian.bamboo.specs.api.builders.permission.Permissions;
import com.atlassian.bamboo.specs.api.builders.permission.PlanPermissions;
import com.atlassian.bamboo.specs.api.builders.plan.PlanIdentifier;
import com.atlassian.bamboo.specs.api.builders.project.Project;
import com.atlassian.bamboo.specs.builders.task.ScpTask;
import com.atlassian.bamboo.specs.builders.task.SshTask;
import com.atlassian.bamboo.specs.util.MapBuilder;

import java.util.Map;

abstract class AbstractSpec {
    private static String sharedCredentialsIdentifier = "prod.docs.typo3.com@srv007.typo3.com";
    private static String deploymentHost = "srv007.typo3.com";
    private static String deploymentUserName = "prod.docs.typo3.com";
    static String linkedRepository = "Intercept";
    static String bambooServerName = "https://bamboo.typo3.com:443";
    static String projectName = "TYPO3 Core";
    static String projectKey = "CORE";

    /**
     * Default permissions on core plans
     */
    PlanPermissions getDefaultPlanPermissions(String projectKey, String planKey) {
        return new PlanPermissions(new PlanIdentifier(projectKey, planKey))
            .permissions(new Permissions()
                .groupPermissions("t3g-team-dev", PermissionType.ADMIN, PermissionType.VIEW, PermissionType.EDIT, PermissionType.BUILD, PermissionType.CLONE)
                .loggedInUserPermissions(PermissionType.VIEW)
                .anonymousUserPermissionView()
            );
    }

    /**
     * Core master pre-merge plan is in "TYPO3 core" project of bamboo
     */
    Project project() {
        return new Project().name(projectName).key(projectKey);
    }

    Map buildExpiryConfig() {
        return new MapBuilder()
            .put("duration", "1")
            .put("period", "days")
            .put("labelsToKeep", "")
            .put("buildsToKeep", "")
            .put("enabled", "true")
            .put("expiryTypeArtifact", "true")
            .build();
    }

    SshTask getAuthenticatedSshTask() {
        return new SshTask()
            .authenticateWithSshSharedCredentials(new SharedCredentialsIdentifier(sharedCredentialsIdentifier))
            .host(deploymentHost)
            .username(deploymentUserName);
    }

    ScpTask getAuthenticatedScpTask() {
        return new ScpTask()
            .authenticateWithSshSharedCredentials(new SharedCredentialsIdentifier(sharedCredentialsIdentifier))
            .host(deploymentHost)
            .username(deploymentUserName);
    }
}

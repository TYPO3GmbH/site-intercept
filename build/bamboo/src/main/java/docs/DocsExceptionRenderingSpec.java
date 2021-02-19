package docs;

import com.atlassian.bamboo.specs.api.BambooSpec;
import com.atlassian.bamboo.specs.api.builders.BambooKey;
import com.atlassian.bamboo.specs.api.builders.BambooOid;
import com.atlassian.bamboo.specs.api.builders.Variable;
import com.atlassian.bamboo.specs.api.builders.credentials.SharedCredentialsIdentifier;
import com.atlassian.bamboo.specs.api.builders.permission.PermissionType;
import com.atlassian.bamboo.specs.api.builders.permission.Permissions;
import com.atlassian.bamboo.specs.api.builders.permission.PlanPermissions;
import com.atlassian.bamboo.specs.api.builders.plan.Job;
import com.atlassian.bamboo.specs.api.builders.plan.Plan;
import com.atlassian.bamboo.specs.api.builders.plan.PlanIdentifier;
import com.atlassian.bamboo.specs.api.builders.plan.Stage;
import com.atlassian.bamboo.specs.api.builders.plan.artifact.Artifact;
import com.atlassian.bamboo.specs.api.builders.plan.artifact.ArtifactSubscription;
import com.atlassian.bamboo.specs.api.builders.plan.branches.BranchCleanup;
import com.atlassian.bamboo.specs.api.builders.plan.branches.PlanBranchManagement;
import com.atlassian.bamboo.specs.api.builders.plan.configuration.AllOtherPluginsConfiguration;
import com.atlassian.bamboo.specs.api.builders.plan.configuration.ConcurrentBuilds;
import com.atlassian.bamboo.specs.api.builders.project.Project;
import com.atlassian.bamboo.specs.api.builders.repository.VcsChangeDetection;
import com.atlassian.bamboo.specs.api.builders.requirement.Requirement;
import com.atlassian.bamboo.specs.builders.repository.git.GitRepository;
import com.atlassian.bamboo.specs.builders.task.ArtifactItem;
import com.atlassian.bamboo.specs.builders.task.CommandTask;
import com.atlassian.bamboo.specs.builders.task.ScpTask;
import com.atlassian.bamboo.specs.builders.task.ScriptTask;
import com.atlassian.bamboo.specs.builders.task.SshTask;
import com.atlassian.bamboo.specs.builders.trigger.RepositoryPollingTrigger;
import com.atlassian.bamboo.specs.util.BambooServer;
import com.atlassian.bamboo.specs.util.MapBuilder;
import java.time.Duration;

@BambooSpec
public class DocsExceptionRenderingSpec {

    public Plan plan() {
        final Plan plan = new Plan(new Project()
                .oid(new BambooOid("3362gawk8uf5"))
                .key(new BambooKey("CORE"))
                .name("TYPO3"),
                "Docs - Exception Rendering",
                new BambooKey("DER"))
                .oid(new BambooOid("32wd8pjcf94x"))
                .pluginConfigurations(new ConcurrentBuilds(),
                        new AllOtherPluginsConfiguration()
                                .configuration(new MapBuilder()
                                        .put("custom.buildExpiryConfig", new MapBuilder()
                                                .put("period", "days")
                                                .put("labelsToKeep", "")
                                                .put("enabled", "true")
                                                .put("expiryTypeArtifact", "true")
                                                .put("duration", "1")
                                                .put("buildsToKeep", "")
                                                .build())
                                        .build()))
                .stages(new Stage("Render Stage")
                                .jobs(new Job("Render",
                                        new BambooKey("JOB1"))
                                        .pluginConfigurations(new AllOtherPluginsConfiguration()
                                                .configuration(new MapBuilder()
                                                        .put("custom", new MapBuilder()
                                                                .put("auto", new MapBuilder()
                                                                        .put("label", "")
                                                                        .put("regex", "")
                                                                        .build())
                                                                .put("clover", new MapBuilder()
                                                                        .put("useLocalLicenseKey", "true")
                                                                        .put("path", "")
                                                                        .put("license", "")
                                                                        .build())
                                                                .put("buildHangingConfig.enabled", "false")
                                                                .put("ncover.path", "")
                                                                .build())
                                                        .build()))
                                        .artifacts(new Artifact()
                                                .name("docs.tgz")
                                                .copyPattern("docs.tgz")
                                                .shared(true)
                                                .required(true))
                                        .tasks(new ScriptTask()
                                                        .description("Render Documentation")
                                                        .inlineBody("if [ \"$(ps -p \"$$\" -o comm=)\" != \"bash\" ]; then\n    bash \"$0\" \"$@\"\n    exit \"$?\"\nfi\n\nset -e\nset -x\n\n# clone repo to project/ and checkout requested branch / tag\nmkdir project\ngit clone https://github.com/TYPO3-Documentation/TYPO3CMS-Exceptions.git project\ncd project && git checkout ${bamboo.VERSION_NUMBER}\ncd ..\n\ntouch project/jobfile.json\ncat << EOF > project/jobfile.json\n{\n    \"Overrides_cfg\": {\n        \"html_theme_options\": {\n            \"docstypo3org\": \"yes\"\n        }\n    }\n}\nEOF\n\ncd project\ncurl -o git-restore-mtime-modified.py https://raw.githubusercontent.com/marble/Toolchain_RenderDocumentation/master/16-Convert-and-fix-and-check/git-restore-mtime/git-restore-mtime-modified.py\npython git-restore-mtime-modified.py --destfile-gitloginfo=.gitloginfo-GENERATED.json\ncd ..\n\nfunction renderDocs() {\n    docker run \\\n        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/project:/PROJECT \\\n        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/RenderedDocumentation/:/RESULT \\\n        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n        --rm \\\n        --entrypoint bash \\\n        t3docs/render-documentation:v2.7.2 \\\n        -c \"/ALL/Menu/mainmenu.sh makehtml -c replace_static_in_html 1 -c make_singlehtml 1 -c jobfile /PROJECT/jobfile.json; chown ${HOST_UID} -R /PROJECT /RESULT\"\n}\nmkdir -p RenderedDocumentation\nmkdir -p FinalDocumentation\n\n# main render call - will render main documentation and localizations\nrenderDocs\n\n# Move files to target dir, including dot files\n(shopt -s dotglob; mv RenderedDocumentation/Result/project/0.0.0/* FinalDocumentation)\n# Remove the directory, all content has been moved\nrm -rf RenderedDocumentation"),
                                                new CommandTask()
                                                        .description("archive result")
                                                        .executable("tar")
                                                        .argument("cfz docs.tgz FinalDocumentation"))
                                        .requirements(new Requirement("system.hasDocker")
                                                .matchValue("1.0")
                                                .matchType(Requirement.MatchType.EQUALS))
                                        .cleanWorkingDirectory(true)),
                        new Stage("Deploy Stage")
                                .jobs(new Job("Deploy",
                                        new BambooKey("DEP"))
                                        .pluginConfigurations(new AllOtherPluginsConfiguration()
                                                .configuration(new MapBuilder()
                                                        .put("custom", new MapBuilder()
                                                                .put("auto", new MapBuilder()
                                                                        .put("label", "")
                                                                        .put("regex", "")
                                                                        .build())
                                                                .put("clover", new MapBuilder()
                                                                        .put("useLocalLicenseKey", "true")
                                                                        .put("path", "")
                                                                        .put("license", "")
                                                                        .build())
                                                                .put("buildHangingConfig.enabled", "false")
                                                                .put("ncover.path", "")
                                                                .build())
                                                        .build()))
                                        .tasks(new SshTask().authenticateWithSshSharedCredentials(new SharedCredentialsIdentifier("prod.docs.typo3.com@prod.docs.typo3.com"))
                                                        .description("mkdir")
                                                        .host("prod.docs.typo3.com")
                                                        .username("prod.docs.typo3.com")
                                                        .command("set -e\nset -x\n\nmkdir -p /srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}"),
                                                new ScpTask()
                                                        .description("copy result")
                                                        .host("prod.docs.typo3.com")
                                                        .username("prod.docs.typo3.com")
                                                        .toRemotePath("/srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}")
                                                        .authenticateWithSshSharedCredentials(new SharedCredentialsIdentifier("prod.docs.typo3.com@prod.docs.typo3.com"))
                                                        .fromArtifact(new ArtifactItem()
                                                                .artifact("docs.tgz")),
                                                new SshTask().authenticateWithSshSharedCredentials(new SharedCredentialsIdentifier("prod.docs.typo3.com@prod.docs.typo3.com"))
                                                        .description("unpack and publish docs")
                                                        .host("prod.docs.typo3.com")
                                                        .username("prod.docs.typo3.com")
                                                        .command("set -e\r\nset -x\r\n\r\ncd /srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}\r\n\r\nmkdir documentation_result\r\ntar xf docs.tgz -C documentation_result\r\n\r\ntarget_dir=\"/srv/vhosts/prod.docs.typo3.com/site/Web/typo3cms/exceptions/${bamboo.VERSION_NUMBER}/en-us\"\r\n\r\necho \"Deploying to ${target_dir}\"\r\n\r\nmkdir -p $target_dir\r\nrm -rf $target_dir/*\r\n\r\nmv documentation_result/FinalDocumentation/* $target_dir\r\n\r\nrm -rf /srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}"))
                                        .requirements(new Requirement("system.hasDocker")
                                                        .matchValue("1.0")
                                                        .matchType(Requirement.MatchType.EQUALS),
                                                new Requirement("system.builder.command.tar"))
                                        .artifactSubscriptions(new ArtifactSubscription()
                                                .artifact("docs.tgz"))
                                        .cleanWorkingDirectory(true)))
                .planRepositories(new GitRepository()
                        .name("TYPO3 Exceptions")
                        .oid(new BambooOid("330iwj9kxg5d"))
                        .url("https://github.com/TYPO3-Documentation/TYPO3CMS-Exceptions.git")
                        .branch("master")
                        .changeDetection(new VcsChangeDetection()))

                .triggers(new RepositoryPollingTrigger()
                        .withPollingPeriod(Duration.ofSeconds(60)))
                .variables(new Variable("VERSION_NUMBER",
                        "master"))
                .planBranchManagement(new PlanBranchManagement()
                        .delete(new BranchCleanup())
                        .notificationForCommitters())
                .forceStopHungBuilds();
        return plan;
    }

    public PlanPermissions planPermission() {
        final PlanPermissions planPermission = new PlanPermissions(new PlanIdentifier("CORE", "DER"))
                .permissions(new Permissions()
                        .userPermissions("susanne.moog", PermissionType.ADMIN, PermissionType.VIEW, PermissionType.CLONE, PermissionType.BUILD, PermissionType.EDIT)
                        .groupPermissions("t3g-team-dev", PermissionType.BUILD, PermissionType.CLONE, PermissionType.ADMIN, PermissionType.VIEW, PermissionType.EDIT)
                        .loggedInUserPermissions(PermissionType.VIEW)
                        .anonymousUserPermissionView());
        return planPermission;
    }

    public static void main(String... argv) {
        //By default credentials are read from the '.credentials' file.
        BambooServer bambooServer = new BambooServer("https://bamboo.typo3.com");
        final DocsExceptionRenderingSpec planSpec = new DocsExceptionRenderingSpec();

        final Plan plan = planSpec.plan();
        bambooServer.publish(plan);

        final PlanPermissions planPermission = planSpec.planPermission();
        bambooServer.publish(planPermission);
    }
}

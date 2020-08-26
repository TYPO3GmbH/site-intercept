package docs;

import com.atlassian.bamboo.specs.api.builders.BambooKey;
import com.atlassian.bamboo.specs.api.builders.Variable;
import com.atlassian.bamboo.specs.api.builders.plan.Job;
import com.atlassian.bamboo.specs.api.builders.plan.Plan;
import com.atlassian.bamboo.specs.api.builders.plan.Stage;
import com.atlassian.bamboo.specs.api.builders.plan.artifact.Artifact;
import com.atlassian.bamboo.specs.api.builders.plan.artifact.ArtifactSubscription;
import com.atlassian.bamboo.specs.api.builders.plan.branches.BranchCleanup;
import com.atlassian.bamboo.specs.api.builders.plan.branches.PlanBranchManagement;
import com.atlassian.bamboo.specs.api.builders.plan.configuration.AllOtherPluginsConfiguration;
import com.atlassian.bamboo.specs.api.builders.plan.configuration.ConcurrentBuilds;
import com.atlassian.bamboo.specs.api.builders.requirement.Requirement;
import com.atlassian.bamboo.specs.builders.task.ArtifactItem;
import com.atlassian.bamboo.specs.builders.task.CommandTask;
import com.atlassian.bamboo.specs.builders.task.ScriptTask;
import com.atlassian.bamboo.specs.builders.trigger.ScheduledTrigger;
import com.atlassian.bamboo.specs.util.MapBuilder;

abstract class AbstractFluidVHSpec extends AbstractSpec {
    protected Plan configurePlan(Plan plan, String version) {
        return plan.pluginConfigurations(new ConcurrentBuilds(),
            new AllOtherPluginsConfiguration()
                .configuration(new MapBuilder()
                    .put("custom.buildExpiryConfig", buildExpiryConfig())
                    .build()))
            .stages(new Stage("Render Stage")
                    .jobs(new Job("Render",
                        new BambooKey("JOB1"))
                        .pluginConfigurations(new AllOtherPluginsConfiguration()
                            .configuration(new MapBuilder()
                                .put("custom", new MapBuilder()
                                    .put("auto", new MapBuilder()
                                        .put("regex", "")
                                        .put("label", "")
                                        .build())
                                    .put("buildHangingConfig.enabled", "false")
                                    .put("ncover.path", "")
                                    .put("clover", new MapBuilder()
                                        .put("path", "")
                                        .put("license", "")
                                        .put("useLocalLicenseKey", "true")
                                        .build())
                                    .build())
                                .build()))
                        .artifacts(new Artifact()
                            .name("docs.tgz")
                            .copyPattern("docs.tgz")
                            .shared(true))
                        .tasks(new ScriptTask()
                                .description("Checkout core repository")
                                .inlineBody("#!/bin/bash\n\n"
                                    + "set -x\n\n"
                                    + "function fixPermissions() {\n"
                                    + "    docker run \\\n"
                                    + "    -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/project:/PROJECT \\\n"
                                    + "    -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/result:/RESULT \\\n"
                                    + "    -e HOME=${HOME} \\\n"
                                    + "    --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n"
                                    + "    --rm \\\n"
                                    + "    --entrypoint bash \\\n"
                                    + "    typo3gmbh/php72:3.0 \\\n"
                                    + "    -c \"set -x; chown -R ${HOST_UID} /PROJECT /RESULT\"\n"
                                    + "}\n\n"
                                    + "fixPermissions\n\n"
                                    + "rm -rf project result\n"
                                    + "mkdir -p project result\n"
                                    + "git clone https://github.com/TYPO3/TYPO3.CMS.git project\n"
                                    + "cd project && git checkout ${bamboo.VERSION_NUMBER}"
                                ),
                            new ScriptTask()
                                .description("Generate core xsd schema files")
                                .inlineBody("#!/bin/bash\n\n"
                                    + "function generateSchemas() {\n"
                                    + "    docker run \\\n"
                                    + "    -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/project:/PROJECT \\\n"
                                    + "    -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/result:/RESULT \\\n"
                                    + "    -e HOME=${HOME} \\\n"
                                    + "    --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n"
                                    + "    --rm \\\n"
                                    + "    --entrypoint bash \\\n"
                                    + "    typo3gmbh/php72:3.0 \\\n"
                                    + "    -c \"set -x; cd /PROJECT && composer require -o -n --no-progress typo3/fluid-schema-generator \\\"^2.1\\\" && mkdir -p /RESULT/schemas/typo3fluid/fluid/latest && ./bin/generateschema TYPO3Fluid\\\\\\Fluid > /RESULT/schemas/typo3fluid/fluid/latest/schema.xsd && mkdir -p /RESULT/schemas/typo3/core/latest && ./bin/generateschema TYPO3\\\\\\CMS\\\\\\Core > /RESULT/schemas/typo3/core/latest/schema.xsd && mkdir -p /RESULT/schemas/typo3/fluid/latest && ./bin/generateschema TYPO3\\\\\\CMS\\\\\\Fluid > /RESULT/schemas/typo3/fluid/latest/schema.xsd && mkdir -p /RESULT/schemas/typo3/backend/latest && ./bin/generateschema TYPO3\\\\\\CMS\\\\\\Backend > /RESULT/schemas/typo3/backend/latest/schema.xsd  ; chown -R ${HOST_UID} /PROJECT /RESULT\"\n"
                                    + "}\n\n"
                                    + "generateSchemas"
                                ),
                            new ScriptTask()
                                .description("Generate RST from schema.xsd files")
                                .inlineBody("#!/bin/bash\n\n"
                                    + "set -x\n\n"
                                    + "chown -R $(id -u):$(id -g) project result\n"
                                    + "rm -rf project\n"
                                    + "mkdir -p project\n"
                                    + "git clone --depth 1 --single-branch --branch master https://github.com/maddy2101/fluid-documentation-generator.git project || exit 1\n\n"
                                    + "function generateRst() {\n"
                                    + "    docker run \\\n"
                                    + "    -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/project:/PROJECT \\\n"
                                    + "    -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/result:/RESULT \\\n"
                                    + "    -e HOME=${HOME} \\\n"
                                    + "    --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n"
                                    + "    --rm \\\n"
                                    + "    --entrypoint bash \\\n"
                                    + "    typo3gmbh/php72:3.0 \\\n"
                                    + "    -c \"set -x; cd /PROJECT && composer install --no-dev -n -o --no-progress && cp -r /RESULT/schemas/* schemas/ && ./bin/generate-fluid-documentation && mkdir -p /RESULT/documentation && cp -r public/* /RESULT/documentation/ ; chown -R ${HOST_UID} /PROJECT /RESULT\"\n"
                                    + "}\n\n"
                                    + "generateRst\n\n"
                                    + "find result/documentation -type f -name '*.xsd' -delete\n"
                                    + "find result/documentation -type f -name '*.json' -delete\n"
                                    + "find result/documentation -type f -name '*.html' -delete"
                                ),
                            new ScriptTask()
                                .description("Render Documentation")
                                .inlineBody(this.getInlineBodyContent()),
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
                                        .put("regex", "")
                                        .put("label", "")
                                        .build())
                                    .put("buildHangingConfig.enabled", "false")
                                    .put("ncover.path", "")
                                    .put("clover", new MapBuilder()
                                        .put("path", "")
                                        .put("license", "")
                                        .put("useLocalLicenseKey", "true")
                                        .build())
                                    .build())
                                .build()))
                        .tasks(getAuthenticatedSshTask()
                                .description("mkdir")
                                .command("set -e\n"
                                    + "set -x\n\n"
                                    + "mkdir -p /srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}"
                                ),
                            getAuthenticatedScpTask()
                                .description("copy result")
                                .toRemotePath("/srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}")
                                .fromArtifact(new ArtifactItem()
                                    .artifact("docs.tgz")),
                            getAuthenticatedSshTask()
                                .description("unpack and publish docs")
                                .command("set -e\n"
                                    + "set -x\n\n"
                                    + "cd /srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}\n\n"
                                    + "mkdir documentation_result\n"
                                    + "tar xf docs.tgz -C documentation_result\n\n"
                                    + "target_dir=\"/srv/vhosts/prod.docs.typo3.com/site/Web/other/typo3/view-helper-reference/${bamboo.VERSION_NUMBER}/en-us\"\n\n"
                                    + "echo \"Deploying to ${target_dir}\"\n\n"
                                    + "mkdir -p $target_dir\n"
                                    + "rm -rf $target_dir/*\n\n"
                                    + "mv documentation_result/FinalDocumentation/* $target_dir\n\n"
                                    + "rm -rf /srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}"
                                )
                        )
                        .requirements(new Requirement("system.hasDocker")
                                .matchValue("1.0")
                                .matchType(Requirement.MatchType.EQUALS),
                            new Requirement("system.builder.command.tar"))
                        .artifactSubscriptions(new ArtifactSubscription()
                            .artifact("docs.tgz"))
                        .cleanWorkingDirectory(true)))
            .triggers(new ScheduledTrigger()
                .cronExpression("0 40 1 ? * *"))
            .variables(new Variable("VERSION_NUMBER", version))
            .planBranchManagement(new PlanBranchManagement()
                .delete(new BranchCleanup())
                .notificationForCommitters())
            .forceStopHungBuilds();
    }

    private String getInlineBodyContent() {
        final String inlineBody = "#!/bin/bash\n\n"
            + "set -x\n\n"
            + "chown -R $(id -u):$(id -g) project result\n"
            + "rm -rf project\n"
            + "mkdir -p project\n"
            + "git clone --depth 1 https://github.com/TYPO3-Documentation/TYPO3CMS-Reference-ViewHelper.git project || exit 1\n\n"
            + "rm -rf project/Documentation/typo3 project/Documentation/typo3fluid\n"
            + "cp -r result/documentation/* project/Documentation/\n\n"
            + "sed -i \"s/version.*=.*/version = ${bamboo.VERSION_NUMBER}/g\" project/Documentation/Settings.cfg\n"
            + "sed -i \"s/release.*=.*/release = ${bamboo.VERSION_NUMBER}/g\" project/Documentation/Settings.cfg\n"
            + "sed -i \"s/github_branch.*=.*/github_branch = ${bamboo.VERSION_NUMBER}/g\" project/Documentation/Settings.cfg\n\n"
            + createJobFile()
            + restoreModificationTime()
            + "function renderDocs() {\n"
            + "    docker run \\\n"
            + "    -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/project:/PROJECT \\\n"
            + "    -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/RenderedDocumentation/:/RESULT \\\n"
            + "    --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n"
            + "    --rm \\\n"
            + "    --entrypoint bash \\\n"
            + "    t3docs/render-documentation:v2.6.1 \\\n"
            + "    -c \"/ALL/Menu/mainmenu.sh makehtml -c replace_static_in_html 1 -c make_singlehtml 1 -c jobfile /PROJECT/jobfile.json; chown ${HOST_UID} -R /PROJECT /RESULT\"\n"
            + "}\n"
            + "mkdir -p RenderedDocumentation\n"
            + "mkdir -p FinalDocumentation\n"
            + "renderDocs\n"
            + "(shopt -s dotglob; mv RenderedDocumentation/Result/project/0.0.0/* FinalDocumentation)\n"
            + "rm -rf RenderedDocumentation project result";
        return inlineBody;
    }

    private String createJobFile() {
        return "touch project/jobfile.json\n"
            + "cat << EOF > project/jobfile.json\n"
            + "{\n"
            + "    \"Overrides_cfg\": {\n"
            + "        \"html_theme_options\": {\n"
            + "            \"docstypo3org\": \"yes\"\n"
            + "        }\n"
            + "    }\n"
            + "}\n"
            + "EOF\n\n";
    }
}

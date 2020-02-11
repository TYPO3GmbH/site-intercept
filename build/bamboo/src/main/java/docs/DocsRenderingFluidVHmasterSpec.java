package docs;

import com.atlassian.bamboo.specs.api.BambooSpec;
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
import com.atlassian.bamboo.specs.util.BambooServer;
import com.atlassian.bamboo.specs.util.MapBuilder;

@BambooSpec
public class DocsRenderingFluidVHmasterSpec extends AbstractSpec {
    private static String planName = "Docs - Rendering Fluid VH master";
    private static String planKey = "DRFVM";

    public static void main(String... argv) {
        // By default credentials are read from the '.credentials' file.
        BambooServer bambooServer = new BambooServer(bambooServerName);
        final DocsRenderingFluidVH95Spec planSpec = new DocsRenderingFluidVH95Spec();
        bambooServer.publish(planSpec.plan());
        bambooServer.publish(planSpec.getDefaultPlanPermissions(projectKey, planKey));
    }

    public Plan plan() {
        return new Plan(project(), planName, planKey)
            .pluginConfigurations(new ConcurrentBuilds(),
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
                            .shared(true)
                            .required(true))
                        .tasks(new ScriptTask()
                                .description("Checkout core repository")
                                .inlineBody("#!/bin/bash\n\nset -x\n\nfunction fixPermissions() {\n    docker run \\\n        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/project:/PROJECT \\\n        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/result:/RESULT \\\n        -e HOME=${HOME} \\\n        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n        --rm \\\n        --entrypoint bash \\\n        typo3gmbh/php72:3.0 \\\n        -c \"set -x; chown -R ${HOST_UID} /PROJECT /RESULT\"\n}\n\nfixPermissions\n\nrm -rf project result\nmkdir -p project result\ngit clone https://github.com/TYPO3/TYPO3.CMS.git project\ncd project && git checkout ${bamboo_VERSION_NUMBER}"),
                            new ScriptTask()
                                .description("Generate core xsd schema files")
                                .inlineBody("#!/bin/bash\n\nfunction generateSchemas() {\n    docker run \\\n        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/project:/PROJECT \\\n        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/result:/RESULT \\\n        -e HOME=${HOME} \\\n        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n        --rm \\\n        --entrypoint bash \\\n        typo3gmbh/php72:3.0 \\\n        -c \"set -x; cd /PROJECT && composer require -o -n --no-progress typo3/fluid-schema-generator \\\"^2.1\\\" && mkdir -p /RESULT/schemas/typo3fluid/fluid/latest && ./bin/generateschema TYPO3Fluid\\\\\\Fluid > /RESULT/schemas/typo3fluid/fluid/latest/schema.xsd && mkdir -p /RESULT/schemas/typo3/core/latest && ./bin/generateschema TYPO3\\\\\\CMS\\\\\\Core > /RESULT/schemas/typo3/core/latest/schema.xsd && mkdir -p /RESULT/schemas/typo3/fluid/latest && ./bin/generateschema TYPO3\\\\\\CMS\\\\\\Fluid > /RESULT/schemas/typo3/fluid/latest/schema.xsd && mkdir -p /RESULT/schemas/typo3/backend/latest && ./bin/generateschema TYPO3\\\\\\CMS\\\\\\Backend > /RESULT/schemas/typo3/backend/latest/schema.xsd  ; chown -R ${HOST_UID} /PROJECT /RESULT\"\n}\n\ngenerateSchemas"),
                            new ScriptTask()
                                .description("Generate RST from schema.xsd files")
                                .inlineBody("#!/bin/bash\n\nset -x\n\nchown -R $(id -u):$(id -g) project result\nrm -rf project\nmkdir -p project\ngit clone --depth 1 --single-branch --branch master https://github.com/maddy2101/fluid-documentation-generator.git project || exit 1\n\nfunction generateRst() {\n    docker run \\\n        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/project:/PROJECT \\\n        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/result:/RESULT \\\n        -e HOME=${HOME} \\\n        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n        --rm \\\n        --entrypoint bash \\\n        typo3gmbh/php72:3.0 \\\n        -c \"set -x; cd /PROJECT && composer install --no-dev -n -o --no-progress && cp -r /RESULT/schemas/* schemas/ && ./bin/generate-fluid-documentation && mkdir -p /RESULT/documentation && cp -r public/* /RESULT/documentation/ ; chown -R ${HOST_UID} /PROJECT /RESULT\"\n}\n\ngenerateRst\n\nfind result/documentation -type f -name '*.xsd' -delete\nfind result/documentation -type f -name '*.json' -delete\nfind result/documentation -type f -name '*.html' -delete"),
                            new ScriptTask()
                                .description("Render Documentation")
                                .inlineBody("#!/bin/bash\n\nset -x\n\nchown -R $(id -u):$(id -g) project result\nrm -rf project\nmkdir -p project\ngit clone --depth 1 https://github.com/TYPO3-Documentation/TYPO3CMS-Reference-ViewHelper.git project || exit 1\n\nrm -rf project/Documentation/typo3 project/Documentation/typo3fluid\ncp -r result/documentation/* project/Documentation/\n\nsed -i \"s/version.*=.*/version = ${bamboo_VERSION_NUMBER}/g\" project/Documentation/Settings.cfg\nsed -i \"s/release.*=.*/release = ${bamboo_VERSION_NUMBER}/g\" project/Documentation/Settings.cfg\nsed -i \"s/github_branch.*=.*/github_branch = ${bamboo_VERSION_NUMBER}/g\" project/Documentation/Settings.cfg\n\ntouch project/jobfile.json\ncat << EOF > project/jobfile.json\n{\n    \"Overrides_cfg\": {\n        \"html_theme_options\": {\n            \"docstypo3org\": \"yes\",\n            \"add_piwik\": \"yes\",\n            \"show_legalinfo\": \"yes\"\n        }\n    }\n}\nEOF\n\nfunction renderDocs() {\n    docker run \\\n        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/project:/PROJECT \\\n        -v /bamboo-data/${BAMBOO_COMPOSE_PROJECT_NAME}/${bamboo_buildKey}/RenderedDocumentation/:/RESULT \\\n        --name ${BAMBOO_COMPOSE_PROJECT_NAME}sib_adhoc \\\n        --rm \\\n        --entrypoint bash \\\n        t3docs/render-documentation:v2.4.0 \\\n        -c \"/ALL/Menu/mainmenu.sh makehtml -c replace_static_in_html 1 -c make_singlehtml 1 -c jobfile /PROJECT/jobfile.json; chown ${HOST_UID} -R /PROJECT /RESULT\"\n}\nmkdir -p RenderedDocumentation\nmkdir -p FinalDocumentation\nrenderDocs\n(shopt -s dotglob; mv RenderedDocumentation/Result/project/0.0.0/* FinalDocumentation)\nrm -rf RenderedDocumentation project result"),
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
                                .command("set -e\r\nset -x\r\n\r\nmkdir -p /srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}"),
                            getAuthenticatedScpTask()
                                .description("copy result")
                                .toRemotePath("/srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}")
                                .fromArtifact(new ArtifactItem()
                                    .artifact("docs.tgz")),
                            getAuthenticatedSshTask()
                                .description("unpack and publish docs")
                                .command("set -e\r\nset -x\r\n\r\ncd /srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}\r\n\r\nmkdir documentation_result\r\ntar xf docs.tgz -C documentation_result\r\n\r\ntarget_dir=\"/srv/vhosts/prod.docs.typo3.com/site/Web/other/typo3/view-helper-reference/master/en-us\"\r\n\r\necho \"Deploying to ${target_dir}\"\r\n\r\nmkdir -p $target_dir\r\nrm -rf $target_dir/*\r\n\r\nmv documentation_result/FinalDocumentation/* $target_dir\r\n\r\nrm -rf /srv/vhosts/prod.docs.typo3.com/deployment/${bamboo.buildResultKey}"))
                        .requirements(new Requirement("system.hasDocker")
                                .matchValue("1.0")
                                .matchType(Requirement.MatchType.EQUALS),
                            new Requirement("system.builder.command.tar"))
                        .artifactSubscriptions(new ArtifactSubscription()
                            .artifact("docs.tgz"))
                        .cleanWorkingDirectory(true)))
            .triggers(new ScheduledTrigger()
                .cronExpression("0 40 1 ? * *"))
            .variables(new Variable("VERSION_NUMBER",
                "master"))
            .planBranchManagement(new PlanBranchManagement()
                .delete(new BranchCleanup())
                .notificationForCommitters())
            .forceStopHungBuilds();
    }
}

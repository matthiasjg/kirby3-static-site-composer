panel.plugin('matthiasjg/kirby3-static-site-composer', {
    fields: {
        staticSiteComposer: {
            props: {
                label: String,
                endpoint: String,
                help: {
                    type: String,
                    default: 'Click the button to compose a static version of the website.',
                },
                progress: {
                    type: String,
                    default: 'Please wait...',
                },
                success: {
                    type: String,
                    default: 'Static site successfully composed',
                },
                error: {
                    type: String,
                    default: 'An error occurred',
                },
            },
            data() {
                return {
                    isBusy: false,
                    response: null,
                };
            },
            template: `
          <div class="matthiasjg-static-site-composer">
            <k-box class="matthiasjg-static-site-composer__container" v-if="!response && !isBusy">
              <k-form @submit="execute()">
                <k-text theme="help" class="matthiasjg-static-site-composer__help">
                  {{ help }}
                </k-text>
                <k-button type="submit" icon="upload" theme="negative" class="matthiasjg-static-site-composer__execute">
                  {{ label }}
                </k-button>
              </k-form>
            </k-box>
  
            <k-box v-if="isBusy" class="matthiasjg-static-site-composer__status">
              <k-text>{{ progress }}</k-text>
            </k-box>
            <k-box v-if="response && response.success" class="matthiasjg-static-site-composer__status" theme="positive">
              <k-text>{{ success }}</k-text>
              <k-text v-if="response.message" class="matthiasjg-static-site-composer__message" theme="help">{{ response.message }}</k-text>
            </k-box>
            <k-box v-if="response && !response.success" class="matthiasjg-static-site-composer__status" theme="negative">
              <k-text>{{ error }}</k-text>
              <k-text v-if="response.message" class="matthiasjg-static-site-composer__message" theme="help">{{ response.message }}</k-text>
            </k-box>
          </div>
        `,
            methods: {
                async execute() {
                    const { endpoint } = this.$props;
                    if (!endpoint) {
                        throw new Error('Error: Config option "matthiasjg.static_site_composer.endpoint" is missing or null. Please set this to any string, e.g. "compose-static-site".');
                    }

                    this.isBusy = true;
                    const response = await this.$api.post(`${endpoint}`);
                    this.isBusy = false;
                    this.response = response;
                },
            },
        },
    },
});
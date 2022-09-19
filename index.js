panel.plugin('matthiasjg/kirby3-static-site-composer', {
  fields: {
    staticSiteComposer: {
      props: {
        label: String,
        endpoint: String,
        help: {
          type: String,
          default: 'Click here to compose a static version of the website of pages and feeds.',
        },
        progress: {
          type: String,
          default: 'Please wait, composing site...',
        },
        success: {
          type: String,
          default: 'Static site successfully composed.',
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
          showPages: false,
          showFeeds: false
        };
      },
      computed: {
        pageItems() {
          return this.response?.files?.pages?.map((page) => ({
            text: page.text,
            link: page.link,
            target: "_blank",
            image: {
              back: "pattern",
              icon: "page",
              color: "gray-500"
            }
          }));
        },
        feedItems() {
          return this.response?.files?.feeds?.map((feed) => ({
            text: feed.text,
            link: feed.link,
            target: "_blank",
            image: {
              back: "pattern",
              icon: "file",
              color: "gray-500"
            }
          }));
        }
      },
      template: `
          <div class="matthiasjg-static-site-composer">
            <section class="k-pages-section" class="matthiasjg-static-site-composer__container">
              <header class="k-section-header">
                <k-headline>Static Site Composer</k-headline>
                <k-button-group>
                  <k-button icon="refresh" :tooltip="help" @click="compose">
                    {{ label }}
                  </k-button>
                </k-button-group>
              </header>

              <k-box v-if="isBusy" class="matthiasjg-static-site-composer__status">
                <k-empty icon="loader">{{ progress }}</k-empty>
              </k-box>

              <k-box v-if="!isBusy && response && response.success" class="matthiasjg-static-site-composer__status" theme="positive">
                <k-text><b>{{ success }}</b></k-text>
                <k-text v-if="response.message" class="matthiasjg-static-site-composer__message" theme="help">
                  {{ response.message }}
                </k-text>
                <k-toggle-field v-model="showPages" label="" :text='["Show generated pages","Hide generated pages"]' icon="page"/>
                <k-items v-if="showPages" :items="pageItems"/>
                <k-toggle-field v-model="showFeeds" label="" :text='["Show generated feeds","Hide generated feeds"]' icon="file"/>
                <k-items v-if="showFeeds" :items="feedItems"/>
              </k-box>

              <k-box v-if="!isBusy && response && !response.success" class="matthiasjg-static-site-composer__status" theme="negative">
                <k-text>{{ error }}</k-text>
                <k-text v-if="response.message" class="matthiasjg-static-site-composer__message" theme="help">
                  {{ response.message }}
                </k-text>
              </k-box>
            </section>
          </div>
        `,
      methods: {
        async compose() {
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
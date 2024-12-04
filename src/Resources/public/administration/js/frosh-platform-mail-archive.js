(()=>{var c=Shopware.Classes.ApiService,h=class extends c{constructor(t,s,a="frosh-mail-archive"){super(t,s,a)}resendMail(t){let s=this.getBasicHeaders({});return this.httpClient.post(`_action/${this.getApiBasePath()}/resend-mail`,{mailId:t},{...this.basicConfig,headers:s}).then(a=>c.handleResponse(a))}downloadMail(t){let s=this.getBasicHeaders({});return this.httpClient.post(`_action/${this.getApiBasePath()}/content`,{mailId:t},{...this.basicConfig,headers:s}).then(a=>{let i=c.handleResponse(a);if(!i.success)return i;let r=window.URL.createObjectURL(new Blob([i.content])),l=document.createElement("a");l.href=r,l.setAttribute("download",i.fileName),document.body.appendChild(l),l.click()})}downloadAttachment(t){let s=this.getBasicHeaders({});return this.httpClient.post(`_action/${this.getApiBasePath()}/attachment`,{attachmentId:t},{...this.basicConfig,headers:s}).then(a=>{let i=c.handleResponse(a);if(!i.success)return i;let r=document.createElement("a");r.href="data:"+i.contentType+";base64,"+i.content,r.setAttribute("download",i.fileName),document.body.appendChild(r),r.click()})}},d=h;var{Application:m}=Shopware;m.addServiceProvider("froshMailArchiveService",e=>{let t=m.getContainer("init");return new d(t.httpClient,e.loginService)});var u=`<sw-page>
    <template #search-bar>
        <sw-search-bar
            initialSearchType="Mail Archive"
            :initialSearch="term"
            @search="onSearch">
        </sw-search-bar>
    </template>
    <template #smart-bar-header>
        <h2>{{ $tc('frosh-mail-archive.title') }}</h2>
    </template>
    <template #content>
        <sw-entity-listing
            ref="table"
            v-if="items"
            :items="items"
            :columns="columns"
            :isLoading="isLoading"
            :repository="mailArchiveRepository"
            @selection-change="onSelectionChanged"
        >
            <template #column-receiver="{ item }">
                <span v-for="(element, index) in item.receiver">
                    {{ element }} &lt;<a :href='\`mailto:\${index}\`'>{{ index }}</a>&gt;
                </span>
            </template>

            <template #column-createdAt="{ item }">
                {{ date(item.createdAt, {hour: '2-digit', minute: '2-digit', second: '2-digit'}) }}
            </template>

            <template #column-transportState="{ item }">

                {{ translateState(item.transportState) }}
                <template v-if="item.transportState === 'failed'">
                    <sw-icon
                            name="regular-exclamation-triangle"
                            color="#f00"
                            class="frosh-mail-archive__data-grid-danger-icon"
                            v-tooltip="{ message: $tc('frosh-mail-archive.list.columns.transportFailed') }"
                            small
                    ></sw-icon>

                    <sw-button
                            square
                            size="small"
                            variant="context"
                            @click="resendMail(item)"
                    >
                        <sw-icon
                                name="regular-undo"
                                class="frosh-mail-archive__data-grid-danger-icon"
                                v-tooltip="{ message: $tc('frosh-mail-archive.list.actions.resendAction') }"
                                small
                        ></sw-icon>
                    </sw-button>

                </template>
            </template>

            <template #detail-action="{ item }">
                <sw-context-menu-item class="sw-entity-listing__context-menu-show-action"
                                      :routerLink="{ name: 'frosh.mail.archive.detail', params: { id: item.id } }">
                    {{ $tc('frosh-mail-archive.list.actions.showAction') }}
                </sw-context-menu-item>

                <sw-context-menu-item class="sw-entity-listing__context-menu-show-action" @click="resendMail(item)">
                    {{ $tc('frosh-mail-archive.list.actions.resendAction') }}
                </sw-context-menu-item>

            </template>

            <template #bulk-additional="{ selectionCount }">
                <a
                        v-if="selectionCount > 0"
                        class="link link-primary"
                        @click="onBulkResendClick"
                >
                    {{ $tc('frosh-mail-archive.list.actions.bulkResendAction') }}

                    <sw-icon
                            name="regular-exclamation-triangle"
                            color="orange"
                            class="frosh-mail-archive__data-grid-warning-icon"
                            v-tooltip="{ message: $tc('frosh-mail-archive.list.actions.bulkResendWarningTooltip') }"
                            small
                    ></sw-icon>
                </a>
            </template>
        </sw-entity-listing>
    </template>

    <template #sidebar>
        <sw-sidebar>
            <sw-sidebar-item
                icon="regular-undo"
                :title="$tc('frosh-mail-archive.list.sidebar.refresh')"
                @click="onRefresh">
            </sw-sidebar-item>

            <sw-sidebar-item icon="regular-filter"
                             :title="$tc('frosh-mail-archive.list.sidebar.filter')">
                <sw-text-field :label="$tc('frosh-mail-archive.list.sidebar.filters.search')"
                               v-model:value="filter.term"></sw-text-field>

                <sw-single-select
                    v-model:value="filter.transportState"
                    :label="$tc('frosh-mail-archive.list.sidebar.filters.transportStateLabel')"
                    :placeholder="$tc('frosh-mail-archive.list.sidebar.filters.transportStatePlaceholder')"
                    :options="transportStateOptions"
                ></sw-single-select>

                <sw-entity-single-select
                    v-model:value="filter.salesChannelId"
                    :label="$tc('frosh-mail-archive.list.sidebar.filters.salesChannel')"
                    entity="sales_channel"
                ></sw-entity-single-select>

                <sw-entity-single-select
                    v-model:value="filter.customerId"
                    :label="$tc('frosh-mail-archive.list.sidebar.filters.customer')"
                    entity="customer"
                >
                    <template #result-label-property="{ item, index, searchTerm, getKey }">
                        <sw-highlight-text
                            :text="\`\${getKey(item, 'firstName')} \${getKey(item, 'lastName')}\${getKey(item, 'lastName') ? ' (' + getKey(item, 'lastName') + ')' : ''}\`"
                            :searchTerm="searchTerm">
                        </sw-highlight-text>
                    </template>
                </sw-entity-single-select>

                <sw-button
                    variant="ghost"
                    @click="resetFilter">
                    {{ $tc('frosh-mail-archive.list.sidebar.filters.resetFilter') }}
                </sw-button>
            </sw-sidebar-item>
        </sw-sidebar>
    </template>
</sw-page>
`;var{Component:w,Mixin:p}=Shopware,{Criteria:o}=Shopware.Data,b=Shopware.Utils;w.register("frosh-mail-archive-index",{template:u,inject:["repositoryFactory","froshMailArchiveService"],mixins:[p.getByName("listing"),p.getByName("notification")],metaInfo(){return{title:this.$createTitle()}},data(){return{page:1,limit:25,total:0,repository:null,items:null,isLoading:!0,filter:{salesChannelId:null,transportState:null,customerId:null,term:null},selectedItems:{}}},computed:{columns(){return[{property:"createdAt",dataIndex:"createdAt",label:"frosh-mail-archive.list.columns.sentDate",primary:!0,routerLink:"frosh.mail.archive.detail"},{property:"transportState",dataIndex:"transportState",label:"frosh-mail-archive.list.columns.transportState",allowResize:!0},{property:"subject",dataIndex:"subject",label:"frosh-mail-archive.list.columns.subject",allowResize:!0,routerLink:"frosh.mail.archive.detail"},{property:"receiver",dataIndex:"receiver",label:"frosh-mail-archive.list.columns.receiver",allowResize:!0}]},mailArchiveRepository(){return this.repositoryFactory.create("frosh_mail_archive")},date(){return Shopware.Filter.getByName("date")},transportStateOptions(){return[{value:"failed",label:this.translateState("failed")},{value:"sent",label:this.translateState("sent")},{value:"pending",label:this.translateState("pending")}]}},methods:{translateState(e){return this.$tc(`frosh-mail-archive.state.${e}`)},getList(){this.isLoading=!0;let e=new o(this.page,this.limit);return e.setTerm(this.term),this.filter.transportState&&e.addFilter(o.equals("transportState",this.filter.transportState)),this.filter.salesChannelId&&e.addFilter(o.equals("salesChannelId",this.filter.salesChannelId)),this.filter.customerId&&e.addFilter(o.equals("customerId",this.filter.customerId)),this.filter.term&&e.setTerm(this.filter.term),e.addSorting(o.sort("createdAt","DESC")),this.mailArchiveRepository.search(e,Shopware.Context.api).then(t=>{this.items=t,this.total=t.total,this.isLoading=!1})},resendMail(e){this.isLoading=!0,this.froshMailArchiveService.resendMail(e.id).then(async()=>{this.createNotificationSuccess({title:this.$tc("frosh-mail-archive.detail.resend-success-notification.title"),message:this.$tc("frosh-mail-archive.detail.resend-success-notification.message")}),await this.getList()}).catch(()=>{this.createNotificationError({title:this.$tc("frosh-mail-archive.detail.resend-error-notification.title"),message:this.$tc("frosh-mail-archive.detail.resend-error-notification.message")})}).finally(()=>{this.isLoading=!1})},onBulkResendClick(){let e=Object.keys(this.selectedItems);e.length!==0&&(this.isLoading=!0,Promise.all(e.map(t=>this.froshMailArchiveService.resendMail(t))).finally(async()=>{this.$refs.table?.resetSelection(),await this.getList(),this.isLoading=!1}))},onSelectionChanged(e){this.selectedItems=e},resetFilter(){this.filter={salesChannelId:null,customerId:null,term:null}}},watch:{filter:{deep:!0,handler:b.debounce(function(){this.getList()},400)}}});var f=`<sw-page class="frosh-mail-archive-detail">
    <template #smart-bar-header>
        <h2 v-if="archive">{{ archive.subject }}</h2>
    </template>

    <template #smart-bar-actions>
        <sw-button variant="ghost" v-if="archive && archive.customer" @click="openCustomer">
            {{ $t('frosh-mail-archive.detail.toolbar.customer') }}
        </sw-button>

        <sw-button-process :isLoading="downloadIsLoading" :process-success="downloadIsSuccessful" @click="downloadMail"
                           @update:process-success="downloadFinish">
            {{ $t('frosh-mail-archive.detail.toolbar.downloadEml') }}
        </sw-button-process>

        <sw-button-process :isLoading="resendIsLoading" :processSuccess="resendIsSuccessful" @click="resendMail"
                           @update:process-success="resendFinish">
            {{ $t('frosh-mail-archive.detail.toolbar.resend') }}
        </sw-button-process>
    </template>

    <template #content>
        <sw-card-view v-if="archive">
            <sw-alert
                    v-if="archive.transportState === 'failed'"
                    variant="warning"
                    class="frosh-mail-archive__detail-alert"
            >
                {{ $t('frosh-mail-archive.detail.alert.transportFailed') }}
            </sw-alert>
            <sw-card
                    :title="$t('frosh-mail-archive.detail.metadata.title')"
                    position-identifier="frosh-mail-archive-metadata"
            >
                <sw-text-field :label="$tc('frosh-mail-archive.detail.metadata.sentDate')" :disabled="true"
                               v-model="createdAtDate"></sw-text-field>
                <sw-text-field :label="$tc('frosh-mail-archive.detail.metadata.sender')" :disabled="true"
                               v-model="senderText"></sw-text-field>
                <sw-text-field :label="$tc('frosh-mail-archive.detail.metadata.receiver')" :disabled="true"
                               v-model="receiverText"></sw-text-field>
                <sw-text-field :label="$tc('frosh-mail-archive.detail.metadata.subject')" :disabled="true"
                               v-model="archive.subject"></sw-text-field>
                <sw-text-field :label="$tc('frosh-mail-archive.detail.metadata.salesChannel')"
                               v-if="archive.salesChannel" :disabled="true"
                               v-model="archive.salesChannel.name"></sw-text-field>
            </sw-card>
            <frosh-mail-resend-history :key="resendKey" :currentMailId="archive.id"
                                       :sourceMailId="archive.sourceMailId ?? archive.id"/>
            <sw-card
                    :title="$t('frosh-mail-archive.detail.content.title')"
                    position-identifier="frosh-mail-archive-content"
            >
                <h4>HTML</h4>
                <iframe :src="htmlText" sandbox frameborder="0"></iframe>

                <h4>Plain</h4>
                <iframe :src="plainText" sandbox frameborder="0"></iframe>
            </sw-card>
            <sw-card :title="$t('frosh-mail-archive.detail.attachments.title')"
                     position-identifier="frosh-mail-archive-attachments"
            >
                <template #grid>
                    <sw-card-section v-if="archive.transportState === 'pending' || archive.attachments.length === 0"
                                     secondary divider="bottom">
                        <sw-alert variant="warning" v-if="archive.transportState === 'pending'">
                            {{ $t('frosh-mail-archive.detail.attachments.attachments-incomplete-alert') }}
                        </sw-alert>
                        <sw-alert v-if="archive.attachments.length === 0">
                            {{ $t('frosh-mail-archive.detail.attachments.no-attachments-alert') }}
                        </sw-alert>
                    </sw-card-section>
                    <sw-data-grid
                            :showSelection="false"
                            :dataSource="archive.attachments"
                            :columns="attachmentsColumns"
                    >
                        <template #column-fileSize="{ item }">
                            <template v-if="item.fileSize < 0">
                                {{ $t('frosh-mail-archive.detail.attachments.size-unknown') }}
                            </template>
                            <template v-else>
                                {{ formatSize(item.fileSize) }}
                            </template>
                        </template>

                        <template #actions="{ item }">
                            <sw-context-menu-item class="sw-entity-listing__context-menu-show-action"
                                                  @click="downloadAttachment(item.id)">
                                {{ $t('frosh-mail-archive.detail.attachments.download') }}
                            </sw-context-menu-item>
                        </template>
                    </sw-data-grid>
                </template>
            </sw-card>
        </sw-card-view>
    </template>
</sw-page>
`;var{Component:y,Mixin:x}=Shopware,{Criteria:$}=Shopware.Data;y.register("frosh-mail-archive-detail",{template:f,inject:["repositoryFactory","froshMailArchiveService"],data(){return{archive:null,resendIsLoading:!1,resendIsSuccessful:!1,downloadIsLoading:!1,downloadIsSuccessful:!1,resendCounter:0}},props:{archiveId:{type:String,required:!0}},mixins:[x.getByName("notification")],created(){this.loadMail()},watch:{archiveId(){this.loadMail()}},computed:{resendKey(){return this.archive.id+this.resendCounter},repository(){return this.repositoryFactory.create("frosh_mail_archive")},createdAtDate(){let e=Shopware.State.getters.adminLocaleLanguage||"en",t={day:"2-digit",month:"2-digit",year:"numeric",hour:"2-digit",minute:"2-digit",second:"2-digit"};return new Intl.DateTimeFormat(e,t).format(new Date(this.archive.createdAt))},receiverText(){let e=[];return Object.keys(this.archive.receiver).forEach(t=>{e.push(`${this.archive.receiver[t]} <${t}>`)}),e.join(",")},senderText(){let e=[];return Object.keys(this.archive.sender).forEach(t=>{e.push(`${this.archive.sender[t]} <${t}>`)}),e.join(",")},htmlText(){return this.getContent(this.archive.htmlText)},plainText(){return this.getContent(this.archive.plainText)},attachmentsColumns(){return[{property:"fileName",label:this.$t("frosh-mail-archive.detail.attachments.file-name"),rawData:!0},{property:"fileSize",label:this.$t("frosh-mail-archive.detail.attachments.size"),rawData:!0},{property:"contentType",label:this.$t("frosh-mail-archive.detail.attachments.type"),rawData:!0}]}},methods:{loadMail(){let e=new $;e.addAssociation("attachments"),this.repository.get(this.archiveId,Shopware.Context.api,e).then(t=>{this.archive=t})},getContent(e){return"data:text/html;base64,"+btoa(unescape(encodeURIComponent(e.replace(/[\u00A0-\u2666]/g,function(t){return"&#"+t.charCodeAt(0)+";"}))))},openCustomer(){this.$router.push({name:"sw.customer.detail",params:{id:this.archive.customer.id}})},resendFinish(){this.resendIsSuccessful=!1},downloadFinish(){this.downloadIsSuccessful=!1},resendMail(){this.resendIsLoading=!0,this.froshMailArchiveService.resendMail(this.archive.id).then(()=>{this.resendIsSuccessful=!0,this.createNotificationSuccess({title:this.$tc("frosh-mail-archive.detail.resend-success-notification.title"),message:this.$tc("frosh-mail-archive.detail.resend-success-notification.message")})}).catch(()=>{this.resendIsSuccessful=!1,this.createNotificationError({title:this.$tc("frosh-mail-archive.detail.resend-error-notification.title"),message:this.$tc("frosh-mail-archive.detail.resend-error-notification.message")})}).finally(()=>{this.resendIsLoading=!1,this.resendCounter++})},downloadMail(){this.downloadIsLoading=!0,this.froshMailArchiveService.downloadMail(this.archive.id).then(()=>{this.downloadIsSuccessful=!0}).catch(()=>{this.downloadIsSuccessful=!1}).finally(()=>{this.downloadIsLoading=!1})},downloadAttachment(e){this.froshMailArchiveService.downloadAttachment(e)},formatSize(e){let a=e;if(Math.abs(e)<1024)return e+" B";let i=["KiB","MiB","GiB","TiB","PiB","EiB","ZiB","YiB"],r=-1,l=10**1;do a/=1024,++r;while(Math.round(Math.abs(a)*l)/l>=1024&&r<i.length-1);return a.toFixed(1)+" "+i[r]}}});var v=`<sw-card :title="$tc('frosh-mail-archive.detail.resend-grid.title')">
    <template #grid>
        <sw-data-grid
            :isLoading="isLoading"
            :data-source="resentMails"
            :columns="columns"
            :allowInlineEdit="false"
            :allowColumnEdit="false"
            :showSettings="false"
            :showSelection="false"
        >
            <template #column-createdAt="{item}">
                <template v-if="currentMailId === item.id">
                    {{ date(item.createdAt) }} ({{ $tc('frosh-mail-archive.detail.resend-grid.currently-selected') }})
                </template>
                <router-link v-else :to="{ name: 'frosh.mail.archive.detail', params: { id: item.id } }">
                    {{ date(item.createdAt) }}
                </router-link>
            </template>
            <template #column-success="{item}">
                <div>
                    <sw-color-badge v-if="item.transportState === 'sent'"
                        color="#37d046"
                        rounded
                    />
                    <sw-color-badge v-else-if="item.transportState === 'failed'"
                            color="#de294c"
                            rounded
                    />
                    <sw-color-badge v-else
                            color="#ffab22"
                            rounded
                    />
                    {{ translateState(item.transportState) }}
                </div>
            </template>
            <template #actions="{ item }">
                <sw-context-menu-item :disabled="currentMailId === item.id"
                                      @click="navigateToDetailPage(item.id)"
                >
                    {{ $tc('frosh-mail-archive.detail.resend-grid.navigate') }}
                </sw-context-menu-item>
            </template>
        </sw-data-grid>
    </template>
</sw-card>
`;var{Criteria:n}=Shopware.Data;Shopware.Component.register("frosh-mail-resend-history",{props:{sourceMailId:{required:!0,type:String},currentMailId:{required:!0,type:String}},template:v,data(){return{resentMails:[],isLoading:!1,columns:[{property:"createdAt",label:this.$tc("frosh-mail-archive.detail.resend-grid.column-created-at"),primary:!0},{property:"success",label:this.$tc("frosh-mail-archive.detail.resend-grid.column-state"),sortable:!1}]}},inject:["repositoryFactory"],computed:{mailArchiveRepository(){return this.repositoryFactory.create("frosh_mail_archive")},date(){return Shopware.Filter.getByName("date")}},async created(){this.isLoading=!0,await this.loadMails(),this.isLoading=!1},methods:{translateState(e){return this.$tc(`frosh-mail-archive.state.${e}`)},async loadMails(){let e=new n;e.addFilter(n.multi("OR",[n.equals("id",this.sourceMailId),n.equals("sourceMailId",this.sourceMailId)])),e.addSorting(n.sort("createdAt","DESC")),this.resentMails=await this.mailArchiveRepository.search(e,Shopware.Context.api)},navigateToDetailPage(e){this.$router.push({name:"frosh.mail.archive.detail",params:{id:e}})}}});Shopware.Module.register("frosh-mail-archive",{type:"plugin",name:"frosh-mail-archive.title",title:"frosh-mail-archive.title",description:"",color:"#243758",icon:"regular-envelope",entity:"frosh_mail_archive",routes:{list:{component:"frosh-mail-archive-index",path:"list",meta:{privilege:"frosh_mail_archive:read"}},detail:{component:"frosh-mail-archive-detail",path:"detail/:id",meta:{parentPath:"frosh.mail.archive.list",privilege:"frosh_mail_archive:read"},props:{default:e=>({archiveId:e.params.id})}}},settingsItem:[{group:"plugins",to:"frosh.mail.archive.list",icon:"regular-envelope",name:"frosh-mail-archive.title",privilege:"frosh_mail_archive:read"}]});})();

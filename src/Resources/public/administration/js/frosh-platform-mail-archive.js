(()=>{var n=Shopware.Classes.ApiService,d=class extends n{constructor(t,r,a="frosh-mail-archive"){super(t,r,a)}resendMail(t){let r=this.getBasicHeaders({});return this.httpClient.post(`_action/${this.getApiBasePath()}/resend-mail`,{mailId:t},{...this.basicConfig,headers:r}).then(a=>n.handleResponse(a))}downloadMail(t){let r=this.getBasicHeaders({});return this.httpClient.post(`_action/${this.getApiBasePath()}/content`,{mailId:t},{...this.basicConfig,headers:r}).then(a=>{let i=n.handleResponse(a);if(!i.success)return i;let s=window.URL.createObjectURL(new Blob([i.content])),l=document.createElement("a");l.href=s,l.setAttribute("download",i.fileName),document.body.appendChild(l),l.click()})}downloadAttachment(t){let r=this.getBasicHeaders({});return this.httpClient.post(`_action/${this.getApiBasePath()}/attachment`,{attachmentId:t},{...this.basicConfig,headers:r}).then(a=>{let i=n.handleResponse(a);if(!i.success)return i;let s=document.createElement("a");s.href="data:"+i.contentType+";base64,"+i.content,s.setAttribute("download",i.fileName),document.body.appendChild(s),s.click()})}},h=d;var{Application:m}=Shopware;m.addServiceProvider("froshMailArchiveService",e=>{let t=m.getContainer("init");return new h(t.httpClient,e.loginService)});var p=`<sw-page>
    <template slot="search-bar">
        <sw-search-bar
            initialSearchType="Mail Archive"
            :initialSearch="term"
            @search="onSearch">
        </sw-search-bar>
    </template>
    <template slot="smart-bar-header">
        <h2>{{ $tc('frosh-mail-archive.title') }}</h2>
    </template>
    <template slot="content">
        <sw-entity-listing
            v-if="items"
            :items="items"
            :columns="columns"
            :isLoading="isLoading"
            :repository="mailArchiveRepository"
        >
            <template slot="column-receiver" slot-scope="{ item }">
                <span v-for="(element, index) in item.receiver">
                    {{ element }} &lt;<a :href='\`mailto:\${index}\`'>{{ index }}</a>&gt;
                </span>
            </template>

            <template slot="column-createdAt" slot-scope="{ item }">
                {{ item.createdAt | date({hour: '2-digit', minute: '2-digit', second: '2-digit'}) }}
                <template v-if="item.transportState === 'failed'">
                    <sw-icon
                        name="regular-exclamation-triangle"
                        color="#f00"
                        class="frosh-mail-archive__data-grid-danger-icon"
                        v-tooltip="{
                            message: $tc('frosh-mail-archive.list.columns.transportFailed')
                        }"
                        small></sw-icon>
                </template>
            </template>

            <template slot="detail-action" slot-scope="{ item }">
                <sw-context-menu-item class="sw-entity-listing__context-menu-show-action"
                                      :routerLink="{ name: 'frosh.mail.archive.detail', params: { id: item.id } }">
                    {{ $tc('frosh-mail-archive.list.columns.action') }}
                </sw-context-menu-item>
            </template>
        </sw-entity-listing>
    </template>

    <sw-sidebar slot="sidebar">
        <sw-sidebar-item
            icon="regular-undo"
            :title="$tc('frosh-mail-archive.list.sidebar.refresh')"
            @click="onRefresh">
        </sw-sidebar-item>

        <sw-sidebar-item icon="regular-filter"
                         :title="$tc('frosh-mail-archive.list.sidebar.filter')">
            <sw-text-field :label="$tc('frosh-mail-archive.list.sidebar.filters.search')"
                           v-model="filter.term"></sw-text-field>

            <sw-entity-single-select
                v-model="filter.salesChannelId"
                :label="$tc('frosh-mail-archive.list.sidebar.filters.salesChannel')"
                entity="sales_channel"
            ></sw-entity-single-select>

            <sw-entity-single-select
                v-model="filter.customerId"
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
</sw-page>
`;var{Component:w,Mixin:b}=Shopware,{Criteria:c}=Shopware.Data,y=Shopware.Utils;w.register("frosh-mail-archive-index",{template:p,inject:["repositoryFactory"],mixins:[b.getByName("listing")],metaInfo(){return{title:this.$createTitle()}},data(){return{page:1,limit:25,total:0,repository:null,items:null,isLoading:!0,filter:{salesChannelId:null,customerId:null,term:null}}},computed:{columns(){return[{property:"createdAt",dataIndex:"createdAt",label:"frosh-mail-archive.list.columns.sentDate",primary:!0,routerLink:"frosh.mail.archive.detail"},{property:"subject",dataIndex:"subject",label:"frosh-mail-archive.list.columns.subject",allowResize:!0,routerLink:"frosh.mail.archive.detail"},{property:"receiver",dataIndex:"receiver",label:"frosh-mail-archive.list.columns.receiver",allowResize:!0}]},mailArchiveRepository(){return this.repositoryFactory.create("frosh_mail_archive")}},methods:{getList(){this.isLoading=!0;let e=new c(this.page,this.limit);return e.setTerm(this.term),this.filter.salesChannelId&&e.addFilter(c.equals("salesChannelId",this.filter.salesChannelId)),this.filter.customerId&&e.addFilter(c.equals("customerId",this.filter.customerId)),this.filter.term&&e.setTerm(this.filter.term),e.addSorting(c.sort("createdAt","DESC")),this.mailArchiveRepository.search(e,Shopware.Context.api).then(t=>{this.items=t,this.total=t.total,this.isLoading=!1})},resetFilter(){this.filter={salesChannelId:null,customerId:null,term:null}}},watch:{filter:{deep:!0,handler:y.debounce(function(){this.getList()},400)}}});var u=`<sw-page class="frosh-mail-archive-detail">
    <template slot="smart-bar-header">
        <h2 v-if="archive">{{ archive.subject }}</h2>
    </template>

    <template slot="smart-bar-actions">
        <sw-button variant="ghost" v-if="archive && archive.customer" @click="openCustomer">
            {{ $tc('frosh-mail-archive.detail.toolbar.customer') }}
        </sw-button>

        <sw-button-process :isLoading="downloadIsLoading" :processSuccess="downloadIsSuccessful" @click="downloadMail"
                           @process-finish="downloadFinish">
            {{ $tc('frosh-mail-archive.detail.toolbar.downloadEml') }}
        </sw-button-process>

        <sw-button-process :isLoading="resendIsLoading" :processSuccess="resendIsSuccessful" @click="resendMail"
                           @process-finish="resendFinish">
            {{ $tc('frosh-mail-archive.detail.toolbar.resend') }}
        </sw-button-process>
    </template>

    <template slot="content">
        <sw-card-view v-if="archive">
            <sw-alert
                v-if="archive.transportState === 'failed'"
                variant="warning"
                class="frosh-mail-archive__detail-alert"
            >
                {{ $tc('frosh-mail-archive.detail.alert.transportFailed') }}
            </sw-alert>
            <sw-card
                :title="$tc('frosh-mail-archive.detail.metadata.title')"
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
                :title="$tc('frosh-mail-archive.detail.content.title')"
                position-identifier="frosh-mail-archive-content"
            >
                <h4>HTML</h4>
                <iframe :src="htmlText" sandbox frameborder="0"></iframe>

                <h4>Plain</h4>
                <iframe :src="plainText" sandbox frameborder="0"></iframe>

                <h4>Attachments: {{ archive.attachments.length }}</h4>

                <sw-data-grid
                    v-if="archive.attachments.length > 0"
                    :showSelection="false"
                    :dataSource="archive.attachments"
                    :columns="attachmentsColumns"
                >
                    <template #column-fileSize="{ item }">
                        <template v-if="item.fileSize < 0">
                            unknown
                        </template>
                        <template v-else>
                            {{ formatSize(item.fileSize) }}
                        </template>
                    </template>

                    <template slot="actions" slot-scope="{ item }">
                        <sw-context-menu-item class="sw-entity-listing__context-menu-show-action"
                                              @click="downloadAttachment(item.id)">
                            Download
                        </sw-context-menu-item>
                    </template>

                </sw-data-grid>
            </sw-card>
        </sw-card-view>
    </template>
</sw-page>
`;var{Component:x,Mixin:I}=Shopware,{Criteria:C}=Shopware.Data;x.register("frosh-mail-archive-detail",{template:u,inject:["repositoryFactory","froshMailArchiveService"],data(){return{archive:null,resendIsLoading:!1,resendIsSuccessful:!1,downloadIsLoading:!1,downloadIsSuccessful:!1,resendCounter:0}},props:{archiveId:{type:String,required:!0}},mixins:[I.getByName("notification")],created(){this.loadMail()},watch:{archiveId(){this.loadMail()}},computed:{resendKey(){return this.archive.id+this.resendCounter},repository(){return this.repositoryFactory.create("frosh_mail_archive")},createdAtDate(){let e=Shopware.State.getters.adminLocaleLanguage||"en",t={day:"2-digit",month:"2-digit",year:"numeric",hour:"2-digit",minute:"2-digit",second:"2-digit"};return new Intl.DateTimeFormat(e,t).format(new Date(this.archive.createdAt))},receiverText(){let e=[];return Object.keys(this.archive.receiver).forEach(t=>{e.push(`${this.archive.receiver[t]} <${t}>`)}),e.join(",")},senderText(){let e=[];return Object.keys(this.archive.sender).forEach(t=>{e.push(`${this.archive.sender[t]} <${t}>`)}),e.join(",")},htmlText(){return this.getContent(this.archive.htmlText)},plainText(){return this.getContent(this.archive.plainText)},attachmentsColumns(){return[{property:"fileName",label:"Name",rawData:!0},{property:"fileSize",label:"Size",rawData:!0},{property:"contentType",label:"ContentType",rawData:!0}]}},methods:{loadMail(){let e=new C;e.addAssociation("attachments"),this.repository.get(this.archiveId,Shopware.Context.api,e).then(t=>{this.archive=t})},getContent(e){return"data:text/html;base64,"+btoa(unescape(encodeURIComponent(e.replace(/[\u00A0-\u2666]/g,function(t){return"&#"+t.charCodeAt(0)+";"}))))},openCustomer(){this.$router.push({name:"sw.customer.detail",params:{id:this.archive.customer.id}})},resendFinish(){this.resendIsSuccessful=!1},downloadFinish(){this.downloadIsSuccessful=!1},resendMail(){this.resendIsLoading=!0,this.froshMailArchiveService.resendMail(this.archive.id).then(()=>{this.resendIsSuccessful=!0,this.createNotificationSuccess({title:this.$tc("frosh-mail-archive.detail.resend-success-notification.title"),message:this.$tc("frosh-mail-archive.detail.resend-success-notification.message")})}).catch(()=>{this.resendIsSuccessful=!1,this.createNotificationError({title:this.$tc("frosh-mail-archive.detail.resend-error-notification.title"),message:this.$tc("frosh-mail-archive.detail.resend-error-notification.message")})}).finally(()=>{this.resendIsLoading=!1,this.resendCounter++})},downloadMail(){this.downloadIsLoading=!0,this.froshMailArchiveService.downloadMail(this.archive.id).then(()=>{this.downloadIsSuccessful=!0}).catch(()=>{this.downloadIsSuccessful=!1}).finally(()=>{this.downloadIsLoading=!1})},downloadAttachment(e){this.froshMailArchiveService.downloadAttachment(e)},formatSize(e){let a=e;if(Math.abs(e)<1024)return e+" B";let i=["KiB","MiB","GiB","TiB","PiB","EiB","ZiB","YiB"],s=-1,l=10**1;do a/=1024,++s;while(Math.round(Math.abs(a)*l)/l>=1024&&s<i.length-1);return a.toFixed(1)+" "+i[s]}}});var f=`<sw-card :title="$tc('frosh-mail-archive.detail.resend-grid.title')">
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
                    {{ item.createdAt|date }} ({{ $tc('frosh-mail-archive.detail.resend-grid.currently-selected') }})
                </template>
                <router-link v-else :to="{ name: 'frosh.mail.archive.detail', params: { id: item.id } }">
                    {{ item.createdAt|date }}
                </router-link>
            </template>
            <template #column-success="{item}">
                <div v-if="item.transportState === 'sent'">
                    <sw-color-badge
                        color="#37d046"
                        rounded
                    />
                    {{ $tc('frosh-mail-archive.detail.resend-grid.success-label') }}
                </div>
                <div v-else-if="item.transportState === 'failed'">
                    <sw-color-badge
                        color="#de294c"
                        rounded
                    />
                    {{ $tc('frosh-mail-archive.detail.resend-grid.failed-label') }}
                </div>
                <div v-else-if="item.transportState === 'pending'">
                    <sw-color-badge
                        color="#ffab22"
                        rounded
                    />
                    {{ $tc('frosh-mail-archive.detail.resend-grid.pending-label') }}
                </div>
                <div v-else>
                    <sw-color-badge
                        color="#94a6b8"
                        rounded
                    />
                    {{ $tc('frosh-mail-archive.detail.resend-grid.unknown-label') }}
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
`;var{Criteria:o}=Shopware.Data;Shopware.Component.register("frosh-mail-resend-history",{props:{sourceMailId:{required:!0,type:String},currentMailId:{required:!0,type:String}},template:f,data(){return{resentMails:[],isLoading:!1,columns:[{property:"createdAt",label:this.$tc("frosh-mail-archive.detail.resend-grid.column-created-at"),primary:!0},{property:"success",label:this.$tc("frosh-mail-archive.detail.resend-grid.column-state"),sortable:!1}]}},inject:["repositoryFactory"],computed:{mailArchiveRepository(){return this.repositoryFactory.create("frosh_mail_archive")}},async created(){this.isLoading=!0,await this.loadMails(),this.isLoading=!1},methods:{async loadMails(){let e=new o;e.addFilter(o.multi("OR",[o.equals("id",this.sourceMailId),o.equals("sourceMailId",this.sourceMailId)])),e.addSorting(o.sort("createdAt","DESC")),this.resentMails=await this.mailArchiveRepository.search(e,Shopware.Context.api)},navigateToDetailPage(e){this.$router.push({name:"frosh.mail.archive.detail",params:{id:e}})}}});Shopware.Module.register("frosh-mail-archive",{type:"plugin",name:"frosh-mail-archive.title",title:"frosh-mail-archive.title",description:"",color:"#243758",icon:"regular-envelope",entity:"frosh_mail_archive",routes:{list:{component:"frosh-mail-archive-index",path:"list",meta:{privilege:"frosh_mail_archive:read"}},detail:{component:"frosh-mail-archive-detail",path:"detail/:id",meta:{parentPath:"frosh.mail.archive.list",privilege:"frosh_mail_archive:read"},props:{default:e=>({archiveId:e.params.id})}}},settingsItem:[{group:"plugins",to:"frosh.mail.archive.list",icon:"regular-envelope",name:"frosh-mail-archive.title",privilege:"frosh_mail_archive:read"}]});var{Component:M,State:A}=Shopware,{Criteria:v}=Shopware.Data;M.override("sw-admin",{inject:["repositoryFactory","acl"],async created(){if(!this.acl.can("frosh_mail_archive:read"))return;let e=this.repositoryFactory.create("frosh_mail_archive"),t=new v;t.addFilter(v.equals("emlPath",null)),t.setLimit(1),e.searchIds(t).then(r=>{r.total>0&&A.dispatch("notification/createNotification",{variant:"error",system:!0,autoClose:!1,growl:!0,title:this.$tc("frosh-mail-archive.missingMigration.title"),message:this.$tc("frosh-mail-archive.missingMigration.description")})})}});})();

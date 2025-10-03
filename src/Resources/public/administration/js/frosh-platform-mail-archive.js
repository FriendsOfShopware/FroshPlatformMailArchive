(()=>{var c=Shopware.Classes.ApiService,d=class extends c{constructor(t,r,i="frosh-mail-archive"){super(t,r,i)}resendMail(t){let r=this.getBasicHeaders({});return this.httpClient.post(`_action/${this.getApiBasePath()}/resend-mail`,{mailId:t},{...this.basicConfig,headers:r}).then(i=>c.handleResponse(i))}downloadMail(t){let r=this.getBasicHeaders({});return this.httpClient.post(`_action/${this.getApiBasePath()}/content`,{mailId:t},{...this.basicConfig,headers:r}).then(i=>{let a=c.handleResponse(i);if(!a.success)return a;let s=window.URL.createObjectURL(new Blob([a.content])),l=document.createElement("a");l.href=s,l.setAttribute("download",a.fileName),document.body.appendChild(l),l.click()})}downloadAttachment(t){let r=this.getBasicHeaders({});return this.httpClient.post(`_action/${this.getApiBasePath()}/attachment`,{attachmentId:t},{...this.basicConfig,headers:r}).then(i=>{let a=c.handleResponse(i);if(!a.success)return a;let s=document.createElement("a");s.href="data:"+a.contentType+";base64,"+a.content,s.setAttribute("download",a.fileName),document.body.appendChild(s),s.click()})}},h=d;var{Application:m}=Shopware;m.addServiceProvider("froshMailArchiveService",e=>{let t=m.getContainer("init");return new h(t.httpClient,e.loginService)});var p=`<sw-page>
    <template #search-bar>
        <sw-search-bar
            initialSearchType="Mail Archive"
            :initialSearch="term"
            @search="onSearch"
        >
        </sw-search-bar>
    </template>

    <template #smart-bar-header>
        <h2>
            {{ $tc('sw-settings.index.title') }}
            <sw-icon
                name="regular-chevron-right-xs"
                size="16px"
                deprecated
            />
            {{ $tc('frosh-mail-archive.title') }}
        </h2>
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
                    {{ element }}
                    &lt;
                    <a :href="'\`mailto:\${index}\`'">{{ index }}</a>
                    &gt;
                </span>
            </template>

            <template #column-createdAt="{ item, column }">
                <router-link :to="{ name: column.routerLink, params: { id: item.id } }">
                    {{ date(item.createdAt, {hour: '2-digit', minute: '2-digit', second: '2-digit'}) }}
                </router-link>
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
                        deprecated
                    ></sw-icon>
                    <sw-button
                        square
                        size="small"
                        variant="context"
                        @click="resendMail(item)"
                        deprecated
                    >
                        <sw-icon
                            name="regular-undo"
                            class="frosh-mail-archive__data-grid-danger-icon"
                            v-tooltip="{ message: $tc('frosh-mail-archive.list.actions.resendAction') }"
                            small
                            deprecated
                        ></sw-icon>
                    </sw-button>
                </template>
            </template>

            <template #detail-action="{ item }">
                <sw-context-menu-item
                    class="sw-entity-listing__context-menu-show-action"
                    :routerLink="{ name: 'frosh.mail.archive.detail', params: { id: item.id } }"
                >
                    {{ $tc('frosh-mail-archive.list.actions.showAction') }}
                </sw-context-menu-item>
                <sw-context-menu-item
                    class="sw-entity-listing__context-menu-show-action"
                    @click="resendMail(item)"
                >
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
                        deprecated
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
                @click="onRefresh"
            >
            </sw-sidebar-item>
            <sw-sidebar-item
                icon="regular-filter"
                :title="$tc('frosh-mail-archive.list.sidebar.filter')"
            >
                <sw-text-field
                    :label="$tc('frosh-mail-archive.list.sidebar.filters.search')"
                    v-model:value="filter.term"
                    deprecated
                ></sw-text-field>
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
                    <template
                        #result-label-property="{ item, index, searchTerm, getKey }"
                    >
                        <sw-highlight-text
                            :text="\`\${getKey(item, 'firstName')} \${getKey(item, 'lastName')}\${getKey(item, 'lastName') ? ' (' + getKey(item, 'lastName') + ')' : ''}\`"
                            :searchTerm="searchTerm"
                        >
                        </sw-highlight-text>
                    </template>
                </sw-entity-single-select>
                <sw-button
                    variant="ghost"
                    @click="resetFilter"
                    deprecated
                >
                    {{ $tc('frosh-mail-archive.list.sidebar.filters.resetFilter') }}
                </sw-button>
            </sw-sidebar-item>
        </sw-sidebar>
    </template>
</sw-page>`;var{Component:g,Mixin:u}=Shopware,{Criteria:o}=Shopware.Data,S=Shopware.Utils;g.register("frosh-mail-archive-index",{template:p,inject:["repositoryFactory","froshMailArchiveService"],mixins:[u.getByName("listing"),u.getByName("notification")],metaInfo(){return{title:this.$createTitle()}},data(){return{page:1,limit:25,total:0,repository:null,items:null,isLoading:!0,filter:{salesChannelId:null,transportState:null,customerId:null,term:null},selectedItems:{}}},computed:{columns(){return[{property:"createdAt",dataIndex:"createdAt",label:"frosh-mail-archive.list.columns.sentDate",primary:!0,routerLink:"frosh.mail.archive.detail"},{property:"transportState",dataIndex:"transportState",label:"frosh-mail-archive.list.columns.transportState",allowResize:!0},{property:"subject",dataIndex:"subject",label:"frosh-mail-archive.list.columns.subject",allowResize:!0,routerLink:"frosh.mail.archive.detail"},{property:"receiver",dataIndex:"receiver",label:"frosh-mail-archive.list.columns.receiver",allowResize:!0}]},mailArchiveRepository(){return this.repositoryFactory.create("frosh_mail_archive")},date(){return Shopware.Filter.getByName("date")},transportStateOptions(){return[{value:"failed",label:this.translateState("failed")},{value:"sent",label:this.translateState("sent")},{value:"pending",label:this.translateState("pending")},{value:"resent",label:this.translateState("resent")}]}},methods:{translateState(e){return this.$tc(`frosh-mail-archive.state.${e}`)},updateData(e){for(let t in this.filter)this.filter[t]=e[t]??null},saveFilters(){this.updateRoute({limit:this.limit,page:this.page,term:this.term,sortBy:this.sortBy,sortDirection:this.sortDirection,naturalSorting:this.naturalSorting},this.filter)},getList(){this.isLoading=!0;let e=new o(this.page,this.limit);return e.setTerm(this.term),this.filter.transportState&&e.addFilter(o.equals("transportState",this.filter.transportState)),this.filter.salesChannelId&&e.addFilter(o.equals("salesChannelId",this.filter.salesChannelId)),this.filter.customerId&&e.addFilter(o.equals("customerId",this.filter.customerId)),this.filter.term&&e.setTerm(this.filter.term),e.addSorting(o.sort("createdAt","DESC")),this.mailArchiveRepository.search(e,Shopware.Context.api).then(t=>{this.items=t,this.total=t.total,this.isLoading=!1,this.saveFilters()})},resendMail(e){this.isLoading=!0,this.froshMailArchiveService.resendMail(e.id).then(async()=>{this.createNotificationSuccess({title:this.$tc("frosh-mail-archive.detail.resend-success-notification.title"),message:this.$tc("frosh-mail-archive.detail.resend-success-notification.message")}),await this.getList()}).catch(()=>{this.createNotificationError({title:this.$tc("frosh-mail-archive.detail.resend-error-notification.title"),message:this.$tc("frosh-mail-archive.detail.resend-error-notification.message")})}).finally(()=>{this.isLoading=!1})},onBulkResendClick(){let e=Object.keys(this.selectedItems);e.length!==0&&(this.isLoading=!0,Promise.all(e.map(t=>this.froshMailArchiveService.resendMail(t))).finally(async()=>{this.$refs.table?.resetSelection(),await this.getList(),this.isLoading=!1}))},onSelectionChanged(e){this.selectedItems=e},resetFilter(){this.filter={salesChannelId:null,customerId:null,term:null}}},watch:{filter:{deep:!0,handler:S.debounce(function(){this.getList()},400)}}});var f=`<sw-page class="frosh-mail-archive-detail">
    <template #smart-bar-header>
        <h2 v-if="archive">{{ archive.subject }}</h2>
    </template>

    <template #smart-bar-actions>
        <sw-button-process
            :isLoading="downloadIsLoading"
            :process-success="downloadIsSuccessful"
            @click="downloadMail"
            @update:process-success="downloadFinish"
        >
            {{ $t('frosh-mail-archive.detail.toolbar.downloadEml') }}
        </sw-button-process>
        <sw-button-process
            :isLoading="resendIsLoading"
            :processSuccess="resendIsSuccessful"
            @click="resendMail"
            @update:process-success="resendFinish"
        >
            {{ $t('frosh-mail-archive.detail.toolbar.resend') }}
        </sw-button-process>
    </template>

    <template #content>
        <sw-card-view v-if="archive">
            <sw-alert
                v-if="archive.transportState === 'failed'"
                variant="warning"
                class="frosh-mail-archive__detail-alert"
                deprecated
            >
                {{ $t('frosh-mail-archive.detail.alert.transportFailed') }}
            </sw-alert>
            <sw-card
                :title="$t('frosh-mail-archive.detail.metadata.title')"
                position-identifier="frosh-mail-archive-metadata"
            >
                <sw-container
                    columns="1fr 1fr"
                    gap="0px 15px"
                    class="frosh-mail-archive__detail-metadata"
                >
                    <sw-description-list>
                        <dt>
                            {{ $tc('frosh-mail-archive.detail.metadata.receiver') }}
                        </dt>
                        <dd>{{ receiverText }}</dd>
                    </sw-description-list>
                    <sw-description-list>
                        <dt>
                            {{ $tc('frosh-mail-archive.detail.metadata.sender') }}
                        </dt>
                        <dd>{{ senderText }}</dd>
                    </sw-description-list>
                    <sw-description-list>
                        <dt>
                            {{ $tc('frosh-mail-archive.detail.metadata.subject') }}
                        </dt>
                        <dd>{{ archive.subject }}</dd>
                    </sw-description-list>
                    <sw-description-list>
                        <dt>
                            {{ $tc('frosh-mail-archive.detail.metadata.sentDate') }}
                        </dt>
                        <dd>{{ createdAtDate }}</dd>
                    </sw-description-list>
                    <sw-description-list>
                        <dt>
                            {{ $tc('frosh-mail-archive.detail.metadata.salesChannel') }}
                        </dt>
                        <dd v-if="archive.salesChannel">
                            {{ archive.salesChannel.name }}
                        </dd>
                        <dd v-else>-</dd>
                    </sw-description-list>
                    <sw-description-list>
                        <dt>
                            {{ $tc('frosh-mail-archive.detail.metadata.customer') }}
                        </dt>
                        <dd v-if="archive.customer">
                            <router-link
                                :to="{ name: 'sw.customer.detail', params: {id: archive.customerId} }"
                            >
                                {{ archive.customer.customerNumber }}
                                -
                                {{ archive.customer.firstName }}
                                {{ archive.customer.lastName }}
                            </router-link>
                        </dd>
                        <dd v-else>-</dd>
                    </sw-description-list>
                    <sw-description-list>
                        <dt>
                            {{ $tc('frosh-mail-archive.detail.metadata.order') }}
                        </dt>
                        <dd v-if="archive.order">
                            <router-link
                                :to="{ name: 'sw.order.detail', params: {id: archive.orderId}}"
                            >
                                {{ archive.order.orderNumber }}
                            </router-link>
                        </dd>
                        <dd v-else>-</dd>
                    </sw-description-list>
                    <sw-description-list>
                        <dt>
                            {{ $tc('frosh-mail-archive.detail.metadata.flow') }}
                        </dt>
                        <dd v-if="archive.flow">
                            <router-link
                                :to="{ name: 'sw.flow.detail', params: {id: archive.flowId}}"
                            >
                                {{ archive.flow.name }}
                            </router-link>
                        </dd>
                        <dd v-else>-</dd>
                    </sw-description-list>
                </sw-container>
            </sw-card>
            <frosh-mail-resend-history
                :key="resendKey"
                :currentMailId="archive.id"
                :sourceMailId="archive.sourceMailId ?? archive.id"
            />
            <sw-card
                :title="$t('frosh-mail-archive.detail.content.title')"
                position-identifier="frosh-mail-archive-content"
            >
                <template v-if="archive.htmlText">
                    <h4>HTML</h4>
                    <iframe
                        :src="htmlText"
                        sandbox
                        frameborder="0"
                    ></iframe>
                </template>

                <template v-if="archive.plainText">
                    <h4>Plain</h4>
                    <iframe
                        :src="plainText"
                        sandbox
                        frameborder="0"
                    ></iframe>
                </template>
            </sw-card>
            <sw-card
                :title="$t('frosh-mail-archive.detail.attachments.title')"
                position-identifier="frosh-mail-archive-attachments"
            >
                <template #grid>
                    <sw-card-section
                        v-if="archive.transportState === 'pending' || archive.attachments.length === 0"
                        secondary
                        divider="bottom"
                    >
                        <sw-alert
                            variant="warning"
                            v-if="archive.transportState === 'pending'"
                        >
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
                            <sw-context-menu-item
                                class="sw-entity-listing__context-menu-show-action"
                                @click="downloadAttachment(item.id)"
                            >
                                {{ $t('frosh-mail-archive.detail.attachments.download') }}
                            </sw-context-menu-item>
                        </template>
                    </sw-data-grid>
                </template>
            </sw-card>
        </sw-card-view>
    </template>
</sw-page>`;var{Component:y,Mixin:x}=Shopware,{Criteria:$}=Shopware.Data;y.register("frosh-mail-archive-detail",{template:f,inject:["repositoryFactory","froshMailArchiveService"],data(){return{archive:null,resendIsLoading:!1,resendIsSuccessful:!1,downloadIsLoading:!1,downloadIsSuccessful:!1,resendCounter:0}},props:{archiveId:{type:String,required:!0}},mixins:[x.getByName("notification")],created(){this.loadMail()},watch:{archiveId(){this.loadMail()}},computed:{resendKey(){return this.archive.id+this.resendCounter},repository(){return this.repositoryFactory.create("frosh_mail_archive")},createdAtDate(){let e=Shopware.State.getters.adminLocaleLanguage||"en",t={day:"2-digit",month:"2-digit",year:"numeric",hour:"2-digit",minute:"2-digit",second:"2-digit"};return new Intl.DateTimeFormat(e,t).format(new Date(this.archive.createdAt))},receiverText(){let e=[];return Object.keys(this.archive.receiver).forEach(t=>{e.push(`${this.archive.receiver[t]} <${t}>`)}),e.join(",")},senderText(){let e=[];return Object.keys(this.archive.sender).forEach(t=>{e.push(`${this.archive.sender[t]} <${t}>`)}),e.join(",")},htmlText(){return this.getContent(this.archive.htmlText)},plainText(){return this.getContent(this.archive.plainText)},attachmentsColumns(){return[{property:"fileName",label:this.$t("frosh-mail-archive.detail.attachments.file-name"),rawData:!0},{property:"fileSize",label:this.$t("frosh-mail-archive.detail.attachments.size"),rawData:!0},{property:"contentType",label:this.$t("frosh-mail-archive.detail.attachments.type"),rawData:!0}]}},methods:{loadMail(){let e=new $;e.addAssociation("attachments"),e.addAssociation("customer"),e.addAssociation("order"),e.addAssociation("flow"),this.repository.get(this.archiveId,Shopware.Context.api,e).then(t=>{this.archive=t})},getContent(e){let t=new TextEncoder().encode(e),r="";return t.forEach(i=>r+=String.fromCharCode(i)),"data:text/html;charset=utf-8;base64,"+btoa(r)},openCustomer(){this.$router.push({name:"sw.customer.detail",params:{id:this.archive.customer.id}})},resendFinish(){this.resendIsSuccessful=!1},downloadFinish(){this.downloadIsSuccessful=!1},resendMail(){this.resendIsLoading=!0,this.froshMailArchiveService.resendMail(this.archive.id).then(()=>{this.resendIsSuccessful=!0,this.createNotificationSuccess({title:this.$tc("frosh-mail-archive.detail.resend-success-notification.title"),message:this.$tc("frosh-mail-archive.detail.resend-success-notification.message")})}).catch(()=>{this.resendIsSuccessful=!1,this.createNotificationError({title:this.$tc("frosh-mail-archive.detail.resend-error-notification.title"),message:this.$tc("frosh-mail-archive.detail.resend-error-notification.message")})}).finally(()=>{this.resendIsLoading=!1,this.resendCounter++})},downloadMail(){this.downloadIsLoading=!0,this.froshMailArchiveService.downloadMail(this.archive.id).then(()=>{this.downloadIsSuccessful=!0}).catch(()=>{this.downloadIsSuccessful=!1}).finally(()=>{this.downloadIsLoading=!1})},downloadAttachment(e){this.froshMailArchiveService.downloadAttachment(e)},formatSize(e){let i=e;if(Math.abs(e)<1024)return e+" B";let a=["KiB","MiB","GiB","TiB","PiB","EiB","ZiB","YiB"],s=-1,l=10**1;do i/=1024,++s;while(Math.round(Math.abs(i)*l)/l>=1024&&s<a.length-1);return i.toFixed(1)+" "+a[s]}}});var v=`<sw-card :title="$tc('frosh-mail-archive.detail.resend-grid.title')">
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
                    {{ date(item.createdAt) }}
                    (
                    {{ $tc('frosh-mail-archive.detail.resend-grid.currently-selected') }}
                    )
                </template>
                <router-link
                    v-else
                    :to="{ name: 'frosh.mail.archive.detail', params: { id: item.id } }"
                >
                    {{ date(item.createdAt) }}
                </router-link>
            </template>

            <template #column-success="{item}">
                <div>
                    <sw-color-badge
                        v-if="item.transportState === 'sent' || item.transportState === 'resent'"
                        color="#37d046"
                        rounded
                    />
                    <sw-color-badge
                        v-else-if="item.transportState === 'failed'"
                        color="#de294c"
                        rounded
                    />
                    <sw-color-badge
                        v-else
                        color="#ffab22"
                        rounded
                    />
                    {{ translateState(item.transportState) }}
                </div>
            </template>

            <template #actions="{ item }">
                <sw-context-menu-item
                    :disabled="currentMailId === item.id"
                    @click="navigateToDetailPage(item.id)"
                >
                    {{ $tc('frosh-mail-archive.detail.resend-grid.navigate') }}
                </sw-context-menu-item>
            </template>
        </sw-data-grid>
    </template>
</sw-card>`;var{Criteria:n}=Shopware.Data;Shopware.Component.register("frosh-mail-resend-history",{props:{sourceMailId:{required:!0,type:String},currentMailId:{required:!0,type:String}},template:v,data(){return{resentMails:[],isLoading:!1,columns:[{property:"createdAt",label:this.$tc("frosh-mail-archive.detail.resend-grid.column-created-at"),primary:!0},{property:"success",label:this.$tc("frosh-mail-archive.detail.resend-grid.column-state"),sortable:!1}]}},inject:["repositoryFactory"],computed:{mailArchiveRepository(){return this.repositoryFactory.create("frosh_mail_archive")},date(){return Shopware.Filter.getByName("date")}},async created(){this.isLoading=!0,await this.loadMails(),this.isLoading=!1},methods:{translateState(e){return this.$tc(`frosh-mail-archive.state.${e}`)},async loadMails(){let e=new n;e.addFilter(n.multi("OR",[n.equals("id",this.sourceMailId),n.equals("sourceMailId",this.sourceMailId)])),e.addSorting(n.sort("createdAt","DESC")),this.resentMails=await this.mailArchiveRepository.search(e,Shopware.Context.api)},navigateToDetailPage(e){this.$router.push({name:"frosh.mail.archive.detail",params:{id:e}})}}});Shopware.Module.register("frosh-mail-archive",{type:"plugin",name:"frosh-mail-archive.title",title:"frosh-mail-archive.title",description:"",color:"#9AA8B5",icon:"regular-envelope",entity:"frosh_mail_archive",routes:{list:{component:"frosh-mail-archive-index",path:"list",meta:{privilege:"frosh_mail_archive:read",parentPath:"sw.settings.index.plugins"}},detail:{component:"frosh-mail-archive-detail",path:"detail/:id",meta:{parentPath:"frosh.mail.archive.list",privilege:"frosh_mail_archive:read"},props:{default:e=>({archiveId:e.params.id})}}},settingsItem:[{group:"plugins",to:"frosh.mail.archive.list",icon:"regular-envelope",name:"frosh-mail-archive.title",privilege:"frosh_mail_archive:read"}]});})();

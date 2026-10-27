(()=>{var n=Shopware.Classes.ApiService,c=class extends n{constructor(t,i,a="frosh-mail-archive"){super(t,i,a)}fetchEmlHeaders(t){let i=this.getBasicHeaders({});return this.httpClient.post(`_action/${this.getApiBasePath()}/fetch-eml-headers`,{mailId:t},{...this.basicConfig,headers:i}).then(a=>n.handleResponse(a))}resendMail(t,i=null){let a=this.getBasicHeaders({}),r={mailId:t};return Array.isArray(i)&&i.length>0&&(r.receiver=i),this.httpClient.post(`_action/${this.getApiBasePath()}/resend-mail`,r,{...this.basicConfig,headers:a}).then(s=>n.handleResponse(s))}downloadMail(t){let i=this.getBasicHeaders({});return this.httpClient.post(`_action/${this.getApiBasePath()}/content`,{mailId:t},{...this.basicConfig,headers:i}).then(a=>{let r=n.handleResponse(a);if(!r.success)return r;let s=window.URL.createObjectURL(new Blob([r.content])),l=document.createElement("a");l.href=s,l.setAttribute("download",r.fileName),document.body.appendChild(l),l.click()})}downloadAttachment(t){let i=this.getBasicHeaders({});return this.httpClient.post(`_action/${this.getApiBasePath()}/attachment`,{attachmentId:t},{...this.basicConfig,headers:i}).then(a=>{let r=n.handleResponse(a);if(!r.success)return r;let s=document.createElement("a");s.href="data:"+r.contentType+";base64,"+r.content,s.setAttribute("download",r.fileName),document.body.appendChild(s),s.click()})}},h=c;var{Application:m}=Shopware;m.addServiceProvider("froshMailArchiveService",e=>{let t=m.getContainer("init");return new h(t.httpClient,e.loginService)});var p=`<sw-page>
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
                        v-if="acl.can('frosh_mail_archive:resend')"
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
                    v-if="acl.can('frosh_mail_archive:resend')"
                    class="sw-entity-listing__context-menu-show-action"
                    @click="resendMail(item)"
                >
                    {{ $tc('frosh-mail-archive.list.actions.resendAction') }}
                </sw-context-menu-item>
            </template>

            <template #bulk-additional="{ selectionCount }">
                <a
                    v-if="selectionCount > 0 && acl.can('frosh_mail_archive:resend')"
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
                <sw-datepicker
                    v-model:value="filter.dateFrom"
                    dateType="date"
                    :label="$tc('frosh-mail-archive.list.sidebar.filters.dateFrom')"
                    deprecated
                ></sw-datepicker>
                <sw-datepicker
                    v-model:value="filter.dateTo"
                    dateType="date"
                    :label="$tc('frosh-mail-archive.list.sidebar.filters.dateTo')"
                    deprecated
                ></sw-datepicker>
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
</sw-page>`;var{Component:y,Mixin:f}=Shopware,{Criteria:o}=Shopware.Data,S=Shopware.Utils;y.register("frosh-mail-archive-index",{template:p,inject:["acl","repositoryFactory","froshMailArchiveService"],mixins:[f.getByName("listing"),f.getByName("notification")],metaInfo(){return{title:this.$createTitle()}},data(){return{page:1,limit:25,total:0,repository:null,items:null,isLoading:!0,filter:{salesChannelId:null,transportState:null,customerId:null,dateFrom:null,dateTo:null,term:null},selectedItems:{}}},computed:{columns(){return[{property:"createdAt",dataIndex:"createdAt",label:"frosh-mail-archive.list.columns.sentDate",primary:!0,routerLink:"frosh.mail.archive.detail"},{property:"transportState",dataIndex:"transportState",label:"frosh-mail-archive.list.columns.transportState",allowResize:!0},{property:"subject",dataIndex:"subject",label:"frosh-mail-archive.list.columns.subject",allowResize:!0,routerLink:"frosh.mail.archive.detail"},{property:"receiver",dataIndex:"receiver",label:"frosh-mail-archive.list.columns.receiver",allowResize:!0}]},mailArchiveRepository(){return this.repositoryFactory.create("frosh_mail_archive")},date(){return Shopware.Filter.getByName("date")},transportStateOptions(){return[{value:"failed",label:this.translateState("failed")},{value:"sent",label:this.translateState("sent")},{value:"pending",label:this.translateState("pending")},{value:"resent",label:this.translateState("resent")}]}},methods:{translateState(e){return this.$tc(`frosh-mail-archive.state.${e}`)},getDateBoundary(e,t){let i=new Date(e.length===10?`${e}T00:00:00`:e);return t?i.setHours(23,59,59,999):i.setHours(0,0,0,0),i.toISOString()},updateData(e){for(let t in this.filter)this.filter[t]=e[t]??null},saveFilters(){this.updateRoute({limit:this.limit,page:this.page,term:this.term,sortBy:this.sortBy,sortDirection:this.sortDirection,naturalSorting:this.naturalSorting},this.filter)},getList(){this.isLoading=!0;let e=new o(this.page,this.limit);e.setTerm(this.term),this.filter.transportState&&e.addFilter(o.equals("transportState",this.filter.transportState)),this.filter.salesChannelId&&e.addFilter(o.equals("salesChannelId",this.filter.salesChannelId)),this.filter.customerId&&e.addFilter(o.equals("customerId",this.filter.customerId));let t={};return this.filter.dateFrom&&(t.gte=this.getDateBoundary(this.filter.dateFrom,!1)),this.filter.dateTo&&(t.lte=this.getDateBoundary(this.filter.dateTo,!0)),Object.keys(t).length>0&&e.addFilter(o.range("createdAt",t)),this.filter.term&&e.setTerm(this.filter.term),e.addSorting(o.sort("createdAt","DESC")),this.mailArchiveRepository.search(e,Shopware.Context.api).then(i=>{this.items=i,this.total=i.total,this.isLoading=!1,this.saveFilters()})},resendMail(e){this.isLoading=!0,this.froshMailArchiveService.resendMail(e.id).then(async()=>{this.createNotificationSuccess({title:this.$tc("frosh-mail-archive.detail.resend-success-notification.title"),message:this.$tc("frosh-mail-archive.detail.resend-success-notification.message")}),await this.getList()}).catch(()=>{this.createNotificationError({title:this.$tc("frosh-mail-archive.detail.resend-error-notification.title"),message:this.$tc("frosh-mail-archive.detail.resend-error-notification.message")})}).finally(()=>{this.isLoading=!1})},onBulkResendClick(){let e=Object.keys(this.selectedItems);if(e.length!==0)return this.isLoading=!0,this.bulkResendFunc(e).catch(()=>{this.createNotificationError({title:this.$tc("frosh-mail-archive.detail.resend-error-notification.title"),message:this.$tc("frosh-mail-archive.detail.resend-error-notification.message")})}).finally(async()=>{try{this.$refs.table?.resetSelection(),await this.getList()}finally{this.isLoading=!1}})},async bulkResendFunc(e){for(let t of e)await this.froshMailArchiveService.resendMail(t)},onSelectionChanged(e){this.selectedItems=e},resetFilter(){this.filter={salesChannelId:null,transportState:null,customerId:null,dateFrom:null,dateTo:null,term:null}}},watch:{filter:{deep:!0,handler:S.debounce(function(){this.getList()},400)}}});var u=`<sw-page class="frosh-mail-archive-detail">
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
            v-if="acl.can('frosh_mail_archive:resend')"
            :isLoading="resendIsLoading"
            :processSuccess="resendIsSuccessful"
            @click="openResendModal"
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
                <div class="frosh-mail-archive__detail-metadata">
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
                            {{ $tc('frosh-mail-archive.detail.metadata.cc') }}
                        </dt>
                        <dd>{{ emlHeaders.Cc ?? '-' }}</dd>
                    </sw-description-list>
                    <sw-description-list>
                        <dt>
                            {{ $tc('frosh-mail-archive.detail.metadata.bcc') }}
                        </dt>
                        <dd>{{ emlHeaders.Bcc  ?? '-' }}</dd>
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
                    <sw-description-list>
                        <dt>
                            {{ $tc('frosh-mail-archive.detail.metadata.mail-template') }}
                        </dt>
                        <dd v-if="archive.mailTemplateId">
                            <router-link
                                :to="{ name: 'sw.mail.template.detail', params: {id: archive.mailTemplateId}}"
                            >
                                {{ archive.mailTemplate.mailTemplateType.translated.name }}
                            </router-link>
                        </dd>
                        <dd v-else>-</dd>
                    </sw-description-list>
                </div>
            </sw-card>
            <sw-card
                :title="$t('frosh-mail-archive.detail.content.title')"
                position-identifier="frosh-mail-archive-content"
            >
                <template v-if="archive.htmlText">
                    <h4>HTML</h4>
                    <iframe
                        title="HTML"
                        :src="htmlText"
                        sandbox
                        frameborder="0"
                    ></iframe>
                </template>

                <template v-if="archive.plainText">
                    <h4>Plain</h4>
                    <iframe
                        title="Plain"
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
                        v-if="archive.attachments.length > 0"
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
            <frosh-mail-resend-history
                :key="resendKey"
                :currentMailId="archive.id"
                :sourceMailId="archive.sourceMailId ?? archive.id"
            />
        </sw-card-view>
        <sw-modal
            v-if="showResendModal && acl.can('frosh_mail_archive:resend')"
            :title="$t('frosh-mail-archive.detail.resend-modal.title')"
            @modal-close="closeResendModal"
        >
            <sw-tagged-field
                v-model:value="resendReceiver"
                :label="$t('frosh-mail-archive.detail.resend-modal.receiverLabel')"
                :help-text="$t('frosh-mail-archive.detail.resend-modal.receiverHelpText')"
            />
            <template #modal-footer>
                <sw-button
                    deprecated
                    @click="closeResendModal"
                >
                    {{ $t('frosh-mail-archive.detail.resend-modal.cancel') }}
                </sw-button>
                <sw-button
                    variant="primary"
                    :isLoading="resendIsLoading"
                    :disabled="resendReceiver.length === 0"
                    deprecated
                    @click="resendMail"
                >
                    {{ $t('frosh-mail-archive.detail.resend-modal.confirm') }}
                </sw-button>
            </template>
        </sw-modal>
    </template>
</sw-page>`;function v(e,t){let i=["default-src 'none'","img-src data: cid:","style-src 'unsafe-inline'","font-src data:","base-uri 'none'","form-action 'none'","navigate-to 'none'"].join("; "),a=t(e,{FORBID_TAGS:["base","embed","form","iframe","meta","object"],FORBID_ATTR:["srcset"]}),r=`<!doctype html><html><head><meta http-equiv="Content-Security-Policy" content="${i}"></head><body>${a}</body></html>`,s=new TextEncoder().encode(r),l="";return s.forEach(g=>{l+=String.fromCharCode(g)}),"data:text/html;charset=utf-8;base64,"+btoa(l)}var{Component:x,Mixin:_}=Shopware,{Criteria:I}=Shopware.Data;x.register("frosh-mail-archive-detail",{template:u,inject:["acl","repositoryFactory","froshMailArchiveService"],data(){return{archive:null,emlHeaders:{},mailLoadRequestId:0,resendIsLoading:!1,resendIsSuccessful:!1,downloadIsLoading:!1,downloadIsSuccessful:!1,resendCounter:0,showResendModal:!1,resendReceiver:[]}},props:{archiveId:{type:String,required:!0}},mixins:[_.getByName("notification")],created(){this.loadMail()},watch:{archiveId(){this.loadMail()}},computed:{resendKey(){return this.archive.id+this.resendCounter},repository(){return this.repositoryFactory.create("frosh_mail_archive")},createdAtDate(){let e=Shopware.State.getters.adminLocaleLanguage||"en",t={day:"2-digit",month:"2-digit",year:"numeric",hour:"2-digit",minute:"2-digit",second:"2-digit"};return new Intl.DateTimeFormat(e,t).format(new Date(this.archive.createdAt))},receiverText(){let e=[];return Object.keys(this.archive.receiver).forEach(t=>{e.push(`${this.archive.receiver[t]} <${t}>`)}),e.join(",")},senderText(){let e=[];return Object.keys(this.archive.sender).forEach(t=>{e.push(`${this.archive.sender[t]} <${t}>`)}),e.join(",")},htmlText(){return this.getContent(this.archive.htmlText)},plainText(){return this.getContent(this.archive.plainText)},attachmentsColumns(){return[{property:"fileName",label:this.$t("frosh-mail-archive.detail.attachments.file-name"),rawData:!0},{property:"fileSize",label:this.$t("frosh-mail-archive.detail.attachments.size"),rawData:!0},{property:"contentType",label:this.$t("frosh-mail-archive.detail.attachments.type"),rawData:!0}]}},methods:{loadMail(){let e=this.archiveId,t=++this.mailLoadRequestId;this.emlHeaders={},this.archive=null;let i=new I;i.addAssociation("attachments"),i.addAssociation("customer"),i.addAssociation("order"),i.addAssociation("flow"),i.addAssociation("mailTemplate.mailTemplateType"),this.repository.get(e,Shopware.Context.api,i).then(a=>{t===this.mailLoadRequestId&&e===this.archiveId&&(this.archive=a)}),this.froshMailArchiveService.fetchEmlHeaders(e).then(a=>{t===this.mailLoadRequestId&&e===this.archiveId&&(this.emlHeaders=a)}).catch(()=>{t===this.mailLoadRequestId&&e===this.archiveId&&(this.emlHeaders={})})},getContent(e){return v(e,this.$sanitize)},openCustomer(){this.$router.push({name:"sw.customer.detail",params:{id:this.archive.customer.id}})},resendFinish(){this.resendIsSuccessful=!1},downloadFinish(){this.downloadIsSuccessful=!1},openResendModal(){this.resendReceiver=Object.entries(this.archive.receiver).map(([e,t])=>t?`${t} <${e}>`:e),this.showResendModal=!0},closeResendModal(){this.showResendModal=!1},resendMail(){this.resendIsLoading=!0,this.froshMailArchiveService.resendMail(this.archive.id,this.resendReceiver).then(()=>{this.resendIsSuccessful=!0,this.showResendModal=!1,this.createNotificationSuccess({title:this.$tc("frosh-mail-archive.detail.resend-success-notification.title"),message:this.$tc("frosh-mail-archive.detail.resend-success-notification.message")})}).catch(()=>{this.resendIsSuccessful=!1,this.createNotificationError({title:this.$tc("frosh-mail-archive.detail.resend-error-notification.title"),message:this.$tc("frosh-mail-archive.detail.resend-error-notification.message")})}).finally(()=>{this.resendIsLoading=!1,this.resendCounter++})},downloadMail(){this.downloadIsLoading=!0,this.froshMailArchiveService.downloadMail(this.archive.id).then(()=>{this.downloadIsSuccessful=!0}).catch(()=>{this.downloadIsSuccessful=!1}).finally(()=>{this.downloadIsLoading=!1})},downloadAttachment(e){this.froshMailArchiveService.downloadAttachment(e)},formatSize(e){let a=e;if(Math.abs(e)<1024)return e+" B";let r=["KiB","MiB","GiB","TiB","PiB","EiB","ZiB","YiB"],s=-1,l=10**1;do a/=1024,++s;while(Math.round(Math.abs(a)*l)/l>=1024&&s<r.length-1);return a.toFixed(1)+" "+r[s]}}});var w=`<sw-card :title="$tc('frosh-mail-archive.detail.resend-grid.title')">
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
</sw-card>`;var{Criteria:d}=Shopware.Data;Shopware.Component.register("frosh-mail-resend-history",{props:{sourceMailId:{required:!0,type:String},currentMailId:{required:!0,type:String}},template:w,data(){return{resentMails:[],isLoading:!1,columns:[{property:"createdAt",label:this.$tc("frosh-mail-archive.detail.resend-grid.column-created-at"),primary:!0},{property:"success",label:this.$tc("frosh-mail-archive.detail.resend-grid.column-state"),sortable:!1}]}},inject:["repositoryFactory"],computed:{mailArchiveRepository(){return this.repositoryFactory.create("frosh_mail_archive")},date(){return Shopware.Filter.getByName("date")}},async created(){this.isLoading=!0,await this.loadMails(),this.isLoading=!1},methods:{translateState(e){return this.$tc(`frosh-mail-archive.state.${e}`)},async loadMails(){let e=new d;e.addFilter(d.multi("OR",[d.equals("id",this.sourceMailId),d.equals("sourceMailId",this.sourceMailId)])),e.addSorting(d.sort("createdAt","DESC")),this.resentMails=await this.mailArchiveRepository.search(e,Shopware.Context.api)},navigateToDetailPage(e){this.$router.push({name:"frosh.mail.archive.detail",params:{id:e}})}}});Shopware.Service("privileges").addPrivilegeMappingEntry({category:"permissions",parent:"settings",key:"frosh_mail_archive",roles:{viewer:{privileges:["frosh_mail_archive:read","frosh_mail_archive_attachment:read"],dependencies:[]},editor:{privileges:["frosh_mail_archive:resend"],dependencies:["frosh_mail_archive.viewer"]}}});Shopware.Module.register("frosh-mail-archive",{type:"plugin",name:"frosh-mail-archive.title",title:"frosh-mail-archive.title",description:"",color:"#9AA8B5",icon:"regular-envelope",entity:"frosh_mail_archive",routes:{list:{component:"frosh-mail-archive-index",path:"list",meta:{privilege:"frosh_mail_archive:read",parentPath:"sw.settings.index.plugins"}},detail:{component:"frosh-mail-archive-detail",path:"detail/:id",meta:{parentPath:"frosh.mail.archive.list",privilege:"frosh_mail_archive:read"},props:{default:e=>({archiveId:e.params.id})}}},settingsItem:[{group:"plugins",to:"frosh.mail.archive.list",icon:"regular-envelope",name:"frosh-mail-archive.title",privilege:"frosh_mail_archive:read"}]});})();

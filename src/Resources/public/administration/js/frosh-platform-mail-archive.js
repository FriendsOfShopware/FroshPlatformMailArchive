(()=>{var o=Shopware.Classes.ApiService,n=class extends o{constructor(t,i,s="frosh-mail-archive"){super(t,i,s)}resendMail(t){let i=this.getBasicHeaders({});return this.httpClient.post(`_action/${this.getApiBasePath()}/resend-mail`,{mailId:t},{...this.basicConfig,headers:i}).then(s=>o.handleResponse(s))}downloadMail(t){let i=this.getBasicHeaders({});return this.httpClient.post(`_action/${this.getApiBasePath()}/download-mail`,{mailId:t},{...this.basicConfig,headers:i}).then(s=>{let a=o.handleResponse(s);if(!a.success)return a;let u=window.URL.createObjectURL(new Blob([a.content])),r=document.createElement("a");r.href=u,r.setAttribute("download",a.fileName),document.body.appendChild(r),r.click()})}},c=n;var{Application:h}=Shopware;h.addServiceProvider("froshMailArchiveService",e=>{let t=h.getContainer("init");return new c(t.httpClient,e.loginService)});var d=`<sw-page>
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
            </template>

            <template slot="detail-action" slot-scope="{ item }">
                <sw-context-menu-item class="sw-entity-listing__context-menu-show-action" :routerLink="{ name: 'frosh.mail.archive.detail', params: { id: item.id } }">
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
            <sw-text-field :label="$tc('frosh-mail-archive.list.sidebar.filters.search')" v-model="filter.term"></sw-text-field>

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
                    <sw-highlight-text :text="\`\${getKey(item, 'firstName')} \${getKey(item, 'lastName')}\${getKey(item, 'lastName') ? ' (' + getKey(item, 'lastName') + ')' : ''}\`"
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
`;var{Component:f,Mixin:v}=Shopware,{Criteria:l}=Shopware.Data,w=Shopware.Utils;f.register("frosh-mail-archive-index",{template:d,inject:["repositoryFactory"],mixins:[v.getByName("listing")],metaInfo(){return{title:this.$createTitle()}},data(){return{page:1,limit:25,total:0,repository:null,items:null,isLoading:!0,filter:{salesChannelId:null,customerId:null,term:null}}},computed:{columns(){return[{property:"createdAt",dataIndex:"createdAt",label:"frosh-mail-archive.list.columns.sentDate",primary:!0,routerLink:"frosh.mail.archive.detail"},{property:"subject",dataIndex:"subject",label:"frosh-mail-archive.list.columns.subject",allowResize:!0,routerLink:"frosh.mail.archive.detail"},{property:"receiver",dataIndex:"receiver",label:"frosh-mail-archive.list.columns.receiver",allowResize:!0}]},mailArchiveRepository(){return this.repositoryFactory.create("frosh_mail_archive")}},methods:{getList(){this.isLoading=!0;let e=new l(this.page,this.limit);return e.setTerm(this.term),this.filter.salesChannelId&&e.addFilter(l.equals("salesChannelId",this.filter.salesChannelId)),this.filter.customerId&&e.addFilter(l.equals("customerId",this.filter.customerId)),this.filter.term&&e.setTerm(this.filter.term),e.addSorting(l.sort("createdAt","DESC")),this.mailArchiveRepository.search(e,Shopware.Context.api).then(t=>{this.items=t,this.total=t.total,this.isLoading=!1})},resetFilter(){this.filter={salesChannelId:null,customerId:null,term:null}}},watch:{filter:{deep:!0,handler:w.debounce(function(){this.getList()},400)}}});var m=`<sw-page class="frosh-mail-archive-detail">
    <template slot="smart-bar-header">
        <h2 v-if="archive">{{ archive.subject }}</h2>
    </template>

    <template slot="smart-bar-actions">
        <sw-button variant="ghost" v-if="archive && archive.customer" @click="openCustomer">
            {{ $tc('frosh-mail-archive.detail.toolbar.customer') }}
        </sw-button>

        <sw-button-process :isLoading="downloadIsLoading" :processSuccess="downloadIsSuccessful" @click="downloadMail">
            {{ $tc('frosh-mail-archive.detail.toolbar.downloadEml') }}
        </sw-button-process>

        <sw-button-process :isLoading="resendIsLoading" :processSuccess="resendIsSuccessful" @click="resendMail">
            {{ $tc('frosh-mail-archive.detail.toolbar.resend') }}
        </sw-button-process>
    </template>

    <template slot="content">
        <sw-card-view v-if="archive">
            <sw-card
                    :title="$tc('frosh-mail-archive.detail.metadata.title')"
                    position-identifier="frosh-mail-archive-metadata"
            >
                <sw-text-field :label="$tc('frosh-mail-archive.detail.metadata.sentDate')" :disabled="true" v-model="createdAtDate"></sw-text-field>
                <sw-text-field :label="$tc('frosh-mail-archive.detail.metadata.sender')" :disabled="true" v-model="senderText"></sw-text-field>
                <sw-text-field :label="$tc('frosh-mail-archive.detail.metadata.receiver')" :disabled="true" v-model="receiverText"></sw-text-field>
                <sw-text-field :label="$tc('frosh-mail-archive.detail.metadata.subject')" :disabled="true" v-model="archive.subject"></sw-text-field>
                <sw-text-field :label="$tc('frosh-mail-archive.detail.metadata.salesChannel')" v-if="archive.salesChannel" :disabled="true" v-model="archive.salesChannel.name"></sw-text-field>
            </sw-card>
            <sw-card
                    :title="$tc('frosh-mail-archive.detail.content.title')"
                    position-identifier="frosh-mail-archive-content"
            >
                <h4>HTML</h4>
                <iframe :src="htmlText" sandbox frameborder="0"></iframe>

                <h4>Plain</h4>
                <iframe :src="plainText" sandbox frameborder="0"></iframe>
            </sw-card>
        </sw-card-view>
    </template>
</sw-page>
`;var{Component:b}=Shopware;b.register("frosh-mail-archive-detail",{template:m,inject:["repositoryFactory","froshMailArchiveService"],data(){return{archive:null,resendIsLoading:!1,resendIsSuccessful:!1,downloadIsLoading:!1,downloadIsSuccessful:!1}},created(){this.repository=this.repositoryFactory.create("frosh_mail_archive"),this.repository.get(this.$route.params.id,Shopware.Context.api).then(e=>{this.archive=e})},computed:{createdAtDate(){let e=Shopware.State.getters.adminLocaleLanguage||"en",t={day:"2-digit",month:"2-digit",year:"numeric",hour:"2-digit",minute:"2-digit",second:"2-digit"};return new Intl.DateTimeFormat(e,t).format(new Date(this.archive.createdAt))},receiverText(){let e=[];return Object.keys(this.archive.receiver).forEach(t=>{e.push(`${this.archive.receiver[t]} <${t}>`)}),e.join(",")},senderText(){let e=[];return Object.keys(this.archive.sender).forEach(t=>{e.push(`${this.archive.sender[t]} <${t}>`)}),e.join(",")},htmlText(){return this.getContent(this.archive.htmlText)},plainText(){return this.getContent(this.archive.plainText)}},methods:{getContent(e){return"data:text/html;base64,"+btoa(unescape(encodeURIComponent(e.replace(/[\u00A0-\u2666]/g,function(t){return"&#"+t.charCodeAt(0)+";"}))))},openCustomer(){this.$router.push({name:"sw.customer.detail",params:{id:this.archive.customer.id}})},resendMail(){this.resendIsLoading=!0,this.froshMailArchiveService.resendMail(this.archive.id).then(()=>{this.resendIsLoading=!1,this.resendIsSuccessful=!0}).catch(()=>{this.resendIsLoading=!1,this.resendIsSuccessful=!1})},downloadMail(){this.downloadIsLoading=!0,this.froshMailArchiveService.downloadMail(this.archive.id).then(()=>{this.downloadIsLoading=!1,this.downloadIsSuccessful=!0}).catch(()=>{this.downloadIsLoading=!1,this.downloadIsSuccessful=!1})}}});Shopware.Module.register("frosh-mail-archive",{type:"plugin",name:"frosh-mail-archive.title",title:"frosh-mail-archive.title",description:"",color:"#243758",icon:"regular-envelope",entity:"frosh_mail_archive",routes:{list:{component:"frosh-mail-archive-index",path:"list"},detail:{component:"frosh-mail-archive-detail",path:"detail/:id",meta:{parentPath:"frosh.mail.archive.list"}}},settingsItem:[{group:"plugins",to:"frosh.mail.archive.list",icon:"regular-envelope",name:"frosh-mail-archive.title"}]});})();

<template>
  <div class="top-bar">
    <nav class="-intro-x mr-auto hidden sm:flex">
        <template v-if="userCompanyLists.length !== 0">
          <Dropdown id="company-dropdown" class="intro-x mr-auto sm:mr-6" data-tw-placement="bottom-start">
            <DropdownToggle tag="div" class="cursor-pointer" role="button">
              <div class="flex flex-row">
                <UmbrellaIcon class="dark:text-slate-300 mr-2" />
                <LoadingIcon icon="puff" v-if="selectedCompanyName === ''"/> <div class="text-gray-700 dark:text-slate-300" v-else><strong>{{ selectedCompanyName }} {{ selectedBranchName === '' ? '': '- ' + selectedBranchName }}</strong></div>
              </div>
              </DropdownToggle>
              <DropdownMenu class="w-96">
                <DropdownContent class="overflow-y-auto h-96 dark:bg-dark-6">
                  <template v-for="(c, cIdx) in userCompanyLists">
                    <DropdownDivider />
                    <DropdownHeader :class="{ 'line-through': ['INACTIVE', 'DELETED'].includes(c.status), 'underline': c.default }">{{ c.name }}</DropdownHeader>
                    <DropdownDivider />
                      <template v-if="!c.branches && c.branches.length == 0">
                          <DropdownItem href="" @click.prevent="switchCompany(c)" class="flex items-center block p-2 transition duration-300 ease-in-out hover:bg-gray-200 dark:hover:bg-dark-3 rounded-md">
                            <span :class="{ 'line-through': ['INACTIVE', 'DELETED'].includes(c.status), 'underline': c.default }">{{ c.name }}</span>
                          </DropdownItem>
                      </template>
                      <template v-else v-for="(br, brIdx) in c.branches">
                        <DropdownItem href="" @click.prevent="switchBranch(c, br)" class="flex items-center block p-2 transition duration-300 ease-in-out hover:bg-gray-200 dark:hover:bg-dark-3 rounded-md">
                          &nbsp;&nbsp;&nbsp;<span :class="{ 'line-through': ['INACTIVE', 'DELETED'].includes(br.status), 'underline': br.is_main }">{{ br.name }}</span>
                        </DropdownItem>
                      </template>
                  </template>
                </DropdownContent>
              </DropdownMenu>            
          </Dropdown>
        </template>
    </nav>

    <div class="mr-auto sm:mr-6 hover:animate-pulse">
      <a href="" class="cursor-pointer" @click.prevent="slideOverShow = true">
        <ArchiveIcon class="dark:text-slate-300" />
      </a>
    </div>

    <Dropdown id="language-dropdown" class="intro-x mr-auto sm:mr-6">
      <DropdownToggle tag="div" role="button" class="cursor-pointer">
        <GlobeIcon class="dark:text-slate-300" />
      </DropdownToggle>
      <DropdownMenu class="w-56">
        <DropdownContent>
            <DropdownItem href="" @click.prevent="switchLanguage('en')" class="flex items-center block p-2 transition duration-300 ease-in-out hover:bg-gray-200 dark:hover:bg-dark-3 rounded-md">
              <img alt="English" src="@/assets/images/us.png" class="w-4 h-4 mr-2" /> <span :class="{ 'font-medium': currentLanguage === 'en' }">English</span>
            </DropdownItem>
            <DropdownItem href="" @click.prevent="switchLanguage('id')" class="flex items-center block p-2 transition duration-300 ease-in-out hover:bg-gray-200 dark:hover:bg-dark-3 rounded-md">
              <img alt="Bahasa Indonesia" src="@/assets/images/id.png" class="w-4 h-4 mr-2" /> <span :class="{ 'font-medium': currentLanguage === 'id' }">Bahasa Indonesia</span>
            </DropdownItem>
        </DropdownContent>
      </DropdownMenu>
    </Dropdown>

    <LoadingIcon icon="puff" v-if="userContext.name === undefined"/>

    <Dropdown id="main-dropdown" class="intro-x w-8 h-8" v-else>
      <DropdownToggle tag="div" role="button" class="dropdown-toggle w-8 h-8 rounded-full overflow-hidden shadow-lg image-fit zoom-in">
        <img alt="" src="@/assets/images/profile2.png"/>
      </DropdownToggle>
      <DropdownMenu class="w-56">
        <DropdownContent class="bg-primary text-white">
          <DropdownHeader tag="div" class="!font-normal">
            <div class="font-medium">{{ userContext !== undefined ? userContext.name:'' }}</div>
            <div class="text-xs text-white/70 mt-0.5 dark:text-slate-500">{{ userContext.email }}</div>
          </DropdownHeader>
          <DropdownDivider class="border-white/[0.08]" />
          <DropdownItem @click="goTo('profile')">
            <UserIcon class="w-4 h-4 mr-2" />
            {{ t('components.top-bar.profile_ddl.profile') }}
          </DropdownItem>
          <DropdownItem @click.prevent="goTo('inbox')">
            <MailIcon class="w-4 h-4 mr-2" />
            {{ t('components.top-bar.profile_ddl.inbox') }}
          </DropdownItem>
          <DropdownItem @click.prevent="goTo('activity')">
            <ActivityIcon class="w-4 h-4 mr-2" />
            {{ t('components.top-bar.profile_ddl.activity') }}
          </DropdownItem>
          <DropdownDivider class="border-white/[0.08]" />
          <DropdownItem @click.prevent="logout">
            <ToggleRightIcon class="w-4 h-4 mr-2" />
            {{ t('components.top-bar.profile_ddl.logout') }}
          </DropdownItem>
        </DropdownContent>
      </DropdownMenu>
    </Dropdown>

    <Modal backdrop="static" :slideOver="true" :show="slideOverShow" @hidden="slideOverShow = false">
      <a @click.prevent="slideOverShow = false" class="absolute top-0 left-0 right-auto mt-4 -ml-12" href="">
        <XIcon class="w-8 h-8 text-slate-400" />
      </a>
      <ModalHeader class="p-5">
        <h2 class="font-medium text-base mr-auto"></h2>
      </ModalHeader>
      
      <ModalBody>
        <div></div>  
      </ModalBody>

      <ModalFooter class="w-full absolute bottom-0">
        <strong>Copyright &copy; {{ (new Date()).getFullYear() }} <a href="https://www.github.com/GitzJoey">GitzJoey</a>&nbsp;&amp;&nbsp;<a href="https://github.com/GitzJoey/DCSLab/graphs/contributors">Contributors</a>.</strong> All rights reserved.<br/> Powered By Coffee &amp; Curiosity.
      </ModalFooter>
    </Modal>
  </div>
</template>

<script setup>
import { ref, watch, computed, inject } from "vue";
import { switchLang, getLang } from "@/lang";
import axios from "@/axios";
import _ from "lodash";
import { useI18n } from "vue-i18n";
import { useRouter } from "vue-router";
import { useUserContextStore } from "@/stores/user-context";

const { t } = useI18n();
const router = useRouter();

const userContextStore = useUserContextStore();
const userContext = computed(() => userContextStore.userContext);
const selectedUserCompany = computed(() => userContextStore.selectedUserCompany);
const selectedUserBranch = computed(() => userContextStore.selectedUserBranch);

const slideOverShow = ref(false);

const selectedCompanyName = ref('');
const selectedBranchName = ref('');

const userCompanyLists = computed(() => {
  if (userContext.value.companies !== undefined && userContext.value.companies.length > 0) {
    return userContext.value.companies;
  } else {
    return [];
  }
});

const callToggleScreenMask = inject('toggleScreenMask');

const switchLanguage = (lang) => {
  switchLang(lang);
  tailwind.Dropdown.getOrCreateInstance(document.querySelector("#language-dropdown")).hide();
}

const currentLanguage = computed(() => {
  return getLang();
});

const switchCompany = (company) => {
  setSelectedCompany(userCompanyLists.value, company);
  tailwind.Dropdown.getOrCreateInstance(document.querySelector("#company-dropdown")).hide();
}

const switchBranch = (company, branch) => {
  setSelectedCompany(userCompanyLists.value, company);
  setSelectedBranch(userCompanyLists.value, company, branch);
  tailwind.Dropdown.getOrCreateInstance(document.querySelector("#company-dropdown")).hide();
}

const setSelectedCompany = (companyLists, selectedCompany) => {
  if (companyLists.length === 0) return;

  if (selectedCompany.hId === '' || selectedCompany.uuid === '') {
    let defaultCompany = _.find(companyLists, { default: true });
    selectedCompanyName.value = defaultCompany.name;
    userContextStore.setSelectedUserCompany(defaultCompany.hId, defaultCompany.uuid);
  } else {
    _.forEach(companyLists, function(item) {
      if (selectedCompany.hId === item.hId && selectedCompany.uuid === item.uuid) {
        selectedCompanyName.value = item.name;
        userContextStore.setSelectedUserCompany(item.hId, item.uuid);
      }
    });
  }
}

const setSelectedBranch = (companyLists, selectedCompany, selectedBranch) => {
  if (selectedCompany.hId === '' && selectedCompany.uuid === '') {
    let defaultCompany = _.find(companyLists, { default: true });
    let mainBranch = _.find(defaultCompany.branches, { is_main: true });

    if (mainBranch) {
      selectedBranchName.value = mainBranch.name
      userContextStore.setSelectedUserBranch(mainBranch.hId, mainBranch.uuid);
    }
  } else {
    _.forEach(companyLists, function(item) {
      if (selectedCompany.hId === item.hId && selectedCompany.uuid === item.uuid) {
        let branch = _.find(item.branches, { hId: selectedBranch.hId });
        
        if (branch) {
          selectedBranchName.value = branch.name
          userContextStore.setSelectedUserBranch(branch.hId, branch.uuid);
        }
      }
    });
  }
}

const goTo = (page) => {
  switch(page) {
    case 'profile':
      router.push({ name: 'side-menu-dashboard-profile' });
      break;
    case 'inbox':
      router.push({ name: 'side-menu-dashboard-inbox' });
      break;
    case 'activity':
      router.push({ name: 'side-menu-dashboard-activity' });
      break;
    }
    tailwind.Dropdown.getOrCreateInstance(document.querySelector("#main-dropdown")).hide();
}

const logout = () => {
  tailwind.Dropdown.getOrCreateInstance(document.querySelector("#main-dropdown")).hide();
  callToggleScreenMask();
  axios.post('/logout').then(response => {
    sessionStorage.clear();
    window.location.href = '/';
  }).catch(e => {

  });
}

watch(userContext, () => {
  setSelectedCompany(userContext.value.companies, selectedUserCompany.value);
});

watch(selectedUserCompany, () => {
  setSelectedCompany(userContext.value.companies, selectedUserCompany.value);
}, { deep: true });
</script>

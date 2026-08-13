import { createRouter, createWebHistory } from 'vue-router'
import Home from '../components/Home.vue'
import About from '../components/AboutUs.vue'
import Contact from '../components/Contact.vue'
import BlogDetail from '../components/BlogDetail.vue'
import Blog from '../components/Blog.vue'
import Care from '../components/Care.vue'
import CareGiver from '../components/CareGiver.vue'
import Chronical from '../components/Chronical.vue'
import Discharge from '../components/Discharge.vue'
import LifeCare from '../components/LifeCare.vue'
import LiveIn from '../components/LiveIn.vue'
import PersonalCare from '../components/PersonalCare.vue'
import RespiteCare from '../components/RespiteCare.vue'
import SpecialCare from '../components/SpecialCare.vue'
import Started from '../components/Started.vue'
import Support from '../components/Support.vue'
import Surgery from '../components/Surgery.vue'
import Testimonial from '../components/Testimonial.vue'
import ElderlyService from '../components/ElderlyService.vue'
import Gallery from '../components/Gallery.vue'
import Team from '../components/Team.vue'
import Faq from '../components/faq.vue'
import Dashboard from '../components/DashBoard.vue'
import contactInbox from '../views/contactInbox.vue'
import CareThanks from '../views/carethanks.vue'
import Complaints from '../views/complaints.vue'
import AnalyticsDashboard from '../views/AnalyticsDashboard.vue'
import Login from '../views/login.vue'
import carAllocation from '../views/carAllocation.vue'
import maintenanceLog from '../views/maintenanceLog.vue'
import carDashboard from '../views/carDashboard.vue'
import vehicleDirectory from '../views/vehicleDirectory.vue'
import websiteContent from '../views/websiteContent.vue'
import jobApplications from '../views/jobApplications.vue'
import userManagement from '../views/userManagement.vue'
import routeOptimiser from '../views/routeOptimiser.vue'
import GoogleDriveBrowser from '../views/GoogleDriveBrowser.vue'
import messageRouting from '../views/messageRouting.vue'
import MileageDashboard from '../views/mileage/MileageDashboard.vue'
import MileageEntryForm from '../views/mileage/MileageEntryForm.vue'
import MyMileageEntries from '../views/mileage/MyMileageEntries.vue'
import WeeklyMileageSubmissions from '../views/mileage/WeeklyMileageSubmissions.vue'
import AdminMileageReview from '../views/mileage/AdminMileageReview.vue'
import NewMileageSubmissions from '../views/mileage/NewMileageSubmissions.vue'
import MileageReports from '../views/mileage/MileageReports.vue'
import MileageSettings from '../views/mileage/MileageSettings.vue'
import CarerDirectory from '../views/mileage/CarerDirectory.vue'
import RunDirectory from '../views/mileage/RunDirectory.vue'
import WeeklyMileageBreakdown from '../views/mileage/WeeklyMileageBreakdown.vue'

const routes = [
    {
        path: '/',
        name: 'home',
        component: Home
    },
    {
        path: '/about',
        name: 'about',
        component: About
    },
    {
        path: '/team',
        name: 'team',
        component: Team
    },
    {
        path: '/faq',
        name: 'faq',
        component: Faq
    },
    {
        path: '/contact',
        name: 'contact',
        component: Contact
    },
    {
        path: '/blogdetail',
        name: 'blogdetail',
        component: BlogDetail
    },
    {
        path: '/blog',
        name: 'blog',
        component: Blog
    },
    {
        path: '/care',
        name: 'care',
        component: Care
    },
    {
        path: '/caregiver',
        name: 'caregiver',
        component: CareGiver
    },
    {
        path: '/chronical',
        name: 'chronical',
        component: Chronical
    },
    {
        path: '/discharge',
        name: 'discharge',
        component: Discharge
    },
    {
        path: '/lifecare',
        name: 'lifecare',
        component: LifeCare
    },
    {
        path: '/livein',
        name: 'Live In',
        component: LiveIn
    },
    {
        path: '/personalcare',
        name: 'personalcare',
        component: PersonalCare
    },
    {
        path: '/respitecare',
        name: 'respitecare',
        component: RespiteCare
    },
    {
        path: '/specialcare',
        name: 'specialcare',
        component: SpecialCare
    },
    {
        path: '/started',
        name: 'started',
        component: Started
    },
    {
        path: '/support',
        name: 'support',
        component: Support
    },
    {
        path: '/surgery',
        name: 'surgery',
        component: Surgery
    },
    {
        path: '/testimonial',
        name: 'testimonial',
        component: Testimonial
    },
    {
        path: '/elderlyservice',
        name: 'elderlyservice',
        component: ElderlyService
    },
    {
        path: '/gallery',
        name: 'gallery',
        component: Gallery
    },
    {
        path: '/login',
        name: 'login',
        component: Login
    },
    {
        path: '/dashboard',
        name: 'dashboard',
        component: Dashboard,
        children: [
          {
            name: 'analyticsDashboard',
            path: '/analyticsDashboard',
            component: AnalyticsDashboard
          },
          {
            name: 'contactInbox',
            path: '/contactInbox',
            component: contactInbox
          },
          {
            name: 'jobapplications',
            path: '/jobapplications',
            component: jobApplications
          },
          {
            name: 'complaints',
            path: '/complaints',
            component: Complaints
          },
          {
            name: 'carethanks',
            path: '/carethanks',
            component: CareThanks
          },
          {
            name: 'messagerouting',
            path: '/messagerouting',
            component: messageRouting
          },
          {
            name: 'signup',
            path: '/signup',
            redirect: { name: 'usermanagement' }
          },
          {
            name: 'usermanagement',
            path: '/usermanagement',
            component: userManagement
          },
          {
            name: 'carallocation',
            path: '/carallocation',
            component: carAllocation
          },
          {
            name: 'maintenancelog',
            path: '/maintenancelog',
            component: maintenanceLog
          },
          {
            name: 'vehicledirectory',
            path: '/vehicledirectory',
            component: vehicleDirectory
          },
          {
            name: 'routeoptimiser',
            path: '/routeoptimiser',
            component: routeOptimiser
          },
          {
            name: 'googledrive',
            path: '/googledrive',
            component: GoogleDriveBrowser
          },
          {
            name: 'cardashboard',
            path: '/cardashboard',
            component: carDashboard
          },
          {
            name: 'websitecontent',
            path: '/websitecontent',
            component: websiteContent
          },
          {
            name: 'mileageDashboard',
            path: '/mileage',
            component: MileageDashboard
          },
          {
            name: 'mileageNew',
            path: '/mileage/new',
            component: MileageEntryForm
          },
          {
            name: 'mileageMine',
            path: '/mileage/my-entries',
            component: MyMileageEntries
          },
          {
            name: 'mileageWeekly',
            path: '/mileage/weekly-submissions',
            component: WeeklyMileageSubmissions
          },
          {
            name: 'mileageNewSubmissions',
            path: '/mileage/new-submissions',
            component: NewMileageSubmissions
          },
          {
            name: 'mileageReview',
            path: '/mileage/admin-review',
            component: AdminMileageReview
          },
          {
            name: 'mileageManagerApproval',
            path: '/mileage/manager-approval',
            component: AdminMileageReview,
            props: { defaultStatus: 'pending_manager_approval' }
          },
          {
            name: 'mileageCarerDirectory',
            path: '/mileage/carer-directory',
            component: CarerDirectory
          },
          {
            name: 'mileageRunDirectory',
            path: '/mileage/run-directory',
            component: RunDirectory
          },
          {
            name: 'mileageReports',
            path: '/mileage/reports',
            component: MileageReports
          },
          {
            name: 'mileageBreakdown',
            path: '/mileage/weekly-breakdown',
            component: WeeklyMileageBreakdown
          },
          {
            name: 'mileageSettings',
            path: '/mileage/settings',
            component: MileageSettings
          },
        ]
      },
]

const router = createRouter({
    history: createWebHistory(),
    routes
  })
  
  export default router

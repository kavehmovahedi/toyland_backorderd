import React, {useState} from "react"
import {createRoot} from "react-dom/client"
import {
    Container,
    TextField,
    Stack,
    Stepper,
    Step,
    StepLabel,
    useMediaQuery,
    useTheme,
    StepContent
} from "@mui/material"
import RegStepOne from "./components/regStepOne.jsx"
import RegStepTwo from "./components/regStepTwo.jsx"
import RegStepThree from "./components/regStepThree.jsx"

const Registration = (props) => {
    const theme = useTheme()
    const isMobile = useMediaQuery( theme.breakpoints.down('sm'))

    const [currentStep, setCurrentStep] = useState(0)
    const [data, setData] = useState({
        email: '',
        password:'',
        password_confirmed: ''
    })

    const goToStep = (step) => setCurrentStep(step)

    const nextStep = (passedData) => {
        setData({...data, ...passedData})
        goToStep(currentStep + 1)
    }

    return(
        <Container maxWidth="md">
            <Stepper activeStep={currentStep} orientation={isMobile ? 'vertical' : 'horizontal'} sx={{mb: isMobile ? 0 : 2}}>
                <Step>
                    <StepLabel>Create Account</StepLabel>
                    {isMobile ? (
                        <StepContent>
                            <RegStepOne {...props} data={data}/>
                        </StepContent>
                    ): <></>}
                </Step>
                <Step>
                    <StepLabel>Contact Details</StepLabel>
                    {isMobile ? (
                        <StepContent>
                            <RegStepTwo {...props} goToStep={goToStep} data={data}/>
                        </StepContent>
                    ): <></>}
                </Step>
                <Step>
                    <StepLabel>Complete</StepLabel>
                    {isMobile ? (
                        <StepContent>
                            <RegStepThree/>
                        </StepContent>
                    ) : <></>}
                </Step>
            </Stepper>
            { !isMobile ? (
                <>
                    {currentStep == 0 ? <RegStepOne {...props} nextStep={nextStep} data={data}/> : (
                        currentStep == 1 ? <RegStepTwo {...props} goToStep={goToStep} data={data}/> : <RegStepThree/>)}
                </>
            ) : <></>}
        </Container>
    )
}

export default Registration

const root = createRoot( document.querySelector('#jg_app') )
root.render(<Registration {...jg_data}/>)